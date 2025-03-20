const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const config = {
  outputFile: path.join(__dirname, 'keio_courses.json'),
  credentials: {
    username: 'kaitosumishi@keio.jp',
    password: '0528QBSkaito'
  },
  delay: 1000 // Delay between requests in ms
};

// Helper function for delays
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Main scraping function
async function scrapeCourses() {
  console.log('Starting Keio course scraper...');

  // Load existing courses if any
  let existingCourses = [];
  try {
    if (fs.existsSync(config.outputFile)) {
      const jsonData = fs.readFileSync(config.outputFile, 'utf8');
      const parsedData = JSON.parse(jsonData);
      if (parsedData.courses && Array.isArray(parsedData.courses)) {
        existingCourses = parsedData.courses;
        console.log(`Loaded ${existingCourses.length} existing courses from file.`);
      }
    }
  } catch (error) {
    console.error('Error loading existing courses:', error.message);
  }

  // Initialize courses array with existing courses
  const allCourses = [...existingCourses];
  const processedRegNumbers = new Set();
  
  // Create set of existing course IDs to avoid duplicates
  existingCourses.forEach(course => {
    processedRegNumbers.add(`${course.course_id}_${course.year}`);
  });

  // Launch the browser
  const browser = await puppeteer.launch({ 
    headless: false,
    defaultViewport: null,
    slowMo: 50
  });

  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(30000);

    // Handle logging
    page.on('console', msg => console.log('Browser console:', msg.text()));

    // Login to the system
    await login(page);

    // Set up search parameters
    await setupSearch(page);
    
    // Process search results - all pages
    await processPages(page, allCourses, processedRegNumbers);

    // Save final results
    saveCoursesToFile(allCourses);

  } catch (error) {
    console.error('Error during scraping:', error);
  } finally {
    await browser.close();
    console.log('Browser closed. Scraping complete.');
  }
}

// Login function
async function login(page) {
  console.log('Logging in to Keio syllabus system...');

  try {
    // Navigate to syllabus search page
    console.log('Navigating to syllabus page...');
    await page.goto('https://gslbs.keio.jp/syllabus/search', { waitUntil: 'networkidle2' });
    await delay(1000);
    console.log(`Redirected to: ${page.url()}`);

    // STEP 1: HANDLE USERNAME SCREEN
    console.log('Looking for username field...');
    await page.waitForSelector('input[type="text"]', { visible: true });
    console.log('Username field found, entering username');
    await page.type('input[type="text"]', config.credentials.username);

    // Find and click Next button
    console.log('Looking for Next button...');
    const submitButtonSelector = 'input[type="submit"]';
    await page.click(submitButtonSelector);
    console.log('Clicked Next button');
    
    // Wait for password page
    console.log('Waiting for password page...');
    await delay(3000);

    // Try to find password field
    console.log('Looking for password field...');
    const passwordField = await page.$('input[type="password"]');

    if (passwordField) {
      console.log('Password field found, entering password');
      await page.type('input[type="password"]', config.credentials.password);

      // Find and click Verify button
      console.log('Looking for Verify button...');
      await page.click(submitButtonSelector);
      console.log('Clicked Verify button');
      
      // Wait for login completion
      console.log('Waiting for login to complete...');
      await delay(5000);
      console.log(`Current URL after login: ${page.url()}`);

      // Check if login was successful
      if (!page.url().includes('gslbs.keio.jp')) {
        throw new Error('Login failed. Could not reach syllabus page.');
      }

      console.log('Login successful!');
      return true;
    } else {
      throw new Error('Password field not found');
    }

  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
}

// Setup search parameters
async function setupSearch(page) {
  console.log('Setting up search parameters...');

  try {
    // Set year to 2025
    const yearSelector = 'select[name="KEYWORD_TTBLYR"]';
    await page.waitForSelector(yearSelector);
    await page.select(yearSelector, '2025');
    console.log('Selected year: 2025');
    
    // Select "基盤科目" from the 分野 dropdown
    const fieldSelector = 'select[name="KEYWORD_FLD1CD"]';
    await page.waitForSelector(fieldSelector);
    await page.select(fieldSelector, '基盤科目');
    console.log('Selected field: 基盤科目');
    
    // Uncheck the 3rd year checkbox if needed
    const yearLevelSelector = 'input[name="KEYWORD_LVL"][value="3"]';
    if (await page.$(yearLevelSelector)) {
      const isChecked = await page.evaluate(el => el.checked, await page.$(yearLevelSelector));
      if (isChecked) {
        await page.click(yearLevelSelector);
        console.log('3rd year checkbox unchecked');
      }
    }
    
    // Submit the search form
    const searchButtonSelector = 'button[data-action_id="SYLLABUS_SEARCH_KEYWORD_EXECUTE"]';
    await page.waitForSelector(searchButtonSelector);
    await page.click(searchButtonSelector);
    console.log('Search form submitted');
    
    // Wait for results to load
    await delay(3000);
    
    // Set "表示順" to "科目名順"
    const displayOrderSelector = 'select[name="SEARCH_RESULT_NARABIJUN"]';
    const hasOrderDropdown = await page.$(displayOrderSelector) !== null;
    
    if (hasOrderDropdown) {
      await page.select(displayOrderSelector, '2');
      console.log('Set 表示順 to 科目名順');
      await delay(2000); // Wait for sorting
    } else {
      console.log('Display order dropdown not found');
    }
    
    return true;
  } catch (error) {
    console.error('Error setting up search:', error);
    throw error;
  }
}

// Process all pages one by one
async function processPages(page, allCourses, processedRegNumbers) {
  // First, process page 1
  console.log('Processing page 1...');
  await processCurrentPage(page, allCourses, processedRegNumbers, 1);
  
  // Now process the remaining pages
  let currentPage = 1;
  let hasMorePages = true;
  
  // Take a screenshot of the pagination for debugging
  await page.screenshot({ path: 'pagination.png' });
  
  while (hasMorePages) {
    try {
      // Get all page numbers visible in the pagination
      const pageNumbers = await page.evaluate(() => {
        const pageItems = Array.from(document.querySelectorAll('li.page-item'));
        return pageItems
          .map(item => {
            // Get the text content which might be a page number
            const text = item.textContent.trim();
            const num = parseInt(text);
            if (!isNaN(num)) {
              return {
                number: num,
                active: item.classList.contains('active')
              };
            }
            return null;
          })
          .filter(item => item !== null) // Remove non-numeric items
          .sort((a, b) => a.number - b.number); // Sort by page number
      });
      
      console.log("Available page numbers:", pageNumbers.map(p => `${p.number}${p.active ? ' (active)' : ''}`).join(', '));
      
      // Find the next page number to click (first page number higher than current page)
      const nextPage = pageNumbers.find(p => p.number > currentPage);
      
      if (nextPage) {
        console.log(`Navigating to page ${nextPage.number}...`);
        
        // Click on the next page number
        await page.evaluate((pageNum) => {
          const pageItems = Array.from(document.querySelectorAll('li.page-item'));
          const targetPage = pageItems.find(item => {
            const text = item.textContent.trim();
            return parseInt(text) === pageNum;
          });
          
          if (targetPage) {
            const link = targetPage.querySelector('a');
            if (link) link.click();
          }
        }, nextPage.number);
        
        // Wait for page to load
        await delay(2000);
        
        // Process the page
        currentPage = nextPage.number;
        await processCurrentPage(page, allCourses, processedRegNumbers, currentPage);
      } else {
        // No higher page numbers found, check if there's a "Next" button
        const hasNextButton = await page.evaluate(() => {
          const nextButton = document.querySelector('li.next:not(.disabled) a.page-link');
          return !!nextButton;
        });
        
        if (hasNextButton) {
          console.log('Clicking "Next" button to reveal more pages...');
          
          await page.evaluate(() => {
            const nextButton = document.querySelector('li.next:not(.disabled) a.page-link');
            if (nextButton) nextButton.click();
          });
          
          await delay(2000); // Wait for page to load
          
          // Check what page we landed on
          const newCurrentPage = await page.evaluate(() => {
            const activeItem = document.querySelector('li.page-item.active');
            return activeItem ? parseInt(activeItem.textContent.trim()) : null;
          });
          
          if (newCurrentPage && newCurrentPage > currentPage) {
            console.log(`Landed on page ${newCurrentPage}`);
            currentPage = newCurrentPage;
            await processCurrentPage(page, allCourses, processedRegNumbers, currentPage);
          } else {
            console.log('Could not determine new page number after clicking Next, stopping pagination');
            hasMorePages = false;
          }
        } else {
          console.log('No Next button found, reached the last page');
          hasMorePages = false;
        }
      }
      
      // Take a screenshot after each page navigation for debugging
      await page.screenshot({ path: `after_page_${currentPage}.png` });
      
    } catch (error) {
      console.error(`Error during pagination:`, error);
      hasMorePages = false;
    }
  }
}

// Process a single page
async function processCurrentPage(page, allCourses, processedRegNumbers, pageNumber) {
  try {
    // Get all course links on the current page
    const courseLinks = await page.$$eval('.btn.btn-info.btn-sm.syllabus-detail.slbs-btn-1', links => 
      links.map(link => link.href)
    );
    
    console.log(`Found ${courseLinks.length} course links on page ${pageNumber}`);
    
    if (courseLinks.length === 0) {
      console.log(`No course links found on page ${pageNumber}`);
      return;
    }
    
    // Process each course one by one in the same page
    for (let i = 0; i < courseLinks.length; i++) {
      try {
        const url = courseLinks[i];
        console.log(`Processing course ${i+1}/${courseLinks.length}: ${url}`);
        
        // Navigate to the course page
        await page.goto(url, { waitUntil: 'networkidle2' });
        
        // Extract Japanese details
        const japaneseDetails = await page.evaluate(() => {
          const findValueByLabel = (label) => {
            const tr = Array.from(document.querySelectorAll('tr')).find(el => 
              el.querySelector('th') && el.querySelector('th').textContent.trim().includes(label));
            if (tr) {
              const td = tr.querySelector('td');
              return td ? td.textContent.trim() : '';
            }
            return '';
          };

          return {
            name: document.querySelector('h2.class-name') ? document.querySelector('h2.class-name').textContent.trim() : '',
            field: findValueByLabel('分野'),
            credits: findValueByLabel('単位数'),
            semester: findValueByLabel('学期'),
            professor: findValueByLabel('担当者名'),
            course_id: findValueByLabel('登録番号')
          };
        });
        
        console.log(`Extracted Japanese details for: ${japaneseDetails.name}`);
        
        // Check if already processed
        const courseKey = `${japaneseDetails.course_id}_2025`;
        if (processedRegNumbers.has(courseKey)) {
          console.log(`Skipping already processed course: ${japaneseDetails.name}`);
          continue;
        }
        
        // Switch to English version if available
        let englishDetails = null;
        const hasEnglishLink = await page.$('a[href*="lang=en"]') !== null;
        
        if (hasEnglishLink) {
          await page.click('a[href*="lang=en"]');
          await delay(2000); // Wait for page to load
          
          englishDetails = await page.evaluate(() => {
            const findValueByLabel = (label) => {
              const tr = Array.from(document.querySelectorAll('tr')).find(el => 
                el.querySelector('th') && el.querySelector('th').textContent.trim().includes(label));
              if (tr) {
                const td = tr.querySelector('td');
                return td ? td.textContent.trim() : '';
              }
              return '';
            };

            return {
              name: document.querySelector('h2.class-name') ? document.querySelector('h2.class-name').textContent.trim() : '',
              field: findValueByLabel('Field'),
              credits: findValueByLabel('Credits'),
              semester: findValueByLabel('Academic Year/Semester'),
              professor: findValueByLabel('Lecturer(s)'),
              course_id: findValueByLabel('Registration Number')
            };
          });
          
          console.log(`Extracted English details for: ${englishDetails.name}`);
        } else {
          console.log('English version not available');
          englishDetails = {
            name: japaneseDetails.name,
            field: '',
            credits: '',
            semester: '',
            professor: japaneseDetails.professor,
            course_id: japaneseDetails.course_id
          };
        }
        
        // Create course object
        const isSpringSemester = japaneseDetails.semester?.toLowerCase().includes('春');
        const course = {
          course_id: japaneseDetails.course_id || englishDetails.course_id,
          year: '2025',
          semester: isSpringSemester ? 'spring' : 'fall',
          translations: {
            ja: {
              name: japaneseDetails.name,
              field: japaneseDetails.field,
              credits: japaneseDetails.credits,
              semester: japaneseDetails.semester
            },
            en: {
              name: englishDetails.name,
              field: englishDetails.field,
              credits: englishDetails.credits,
              semester: englishDetails.semester
            }
          },
          professors: [
            {
              name: {
                ja: japaneseDetails.professor,
                en: englishDetails.professor
              },
              department: {
                ja: japaneseDetails.field,
                en: englishDetails.field
              }
            }
          ],
          available_years: ['2025']
        };
        
        // Add to collection
        allCourses.push(course);
        processedRegNumbers.add(courseKey);
        console.log(`Added course: ${course.translations.ja.name}`);
        
        // Save every few courses
        if (i % 5 === 4 || i === courseLinks.length - 1) {
          saveCoursesToFile(allCourses, pageNumber);
        }
        
      } catch (error) {
        console.error(`Error processing course ${i+1}:`, error);
      }
      
      // Go back to search results page between courses
      if (i < courseLinks.length - 1) {
        try {
          // Navigate back to the search results page
          await page.goto('https://gslbs.keio.jp/syllabus/result', { waitUntil: 'networkidle2' });
          await delay(1000);
          
          // If we're on a page other than the first, we need to navigate back to that page
          if (pageNumber > 1) {
            // Find the pagination element with the correct page number
            await page.evaluate((targetPage) => {
              // Look for the correct page number in the pagination
              const pageItems = Array.from(document.querySelectorAll('li.page-item'));
              const targetPageItem = pageItems.find(item => {
                const text = item.textContent.trim();
                return parseInt(text) === targetPage;
              });
              
              // If found, click it
              if (targetPageItem) {
                const link = targetPageItem.querySelector('a');
                if (link) link.click();
              } else {
                // If not visible, use the Next button repeatedly
                let currentActivePage = 1;
                const activeItem = document.querySelector('li.page-item.active');
                if (activeItem) {
                  const activeText = activeItem.textContent.trim();
                  currentActivePage = parseInt(activeText) || 1;
                }
                
                // If we need to advance pages
                if (currentActivePage < targetPage) {
                  // Find and click Next until we reach the correct page
                  const nextButton = document.querySelector('li.next:not(.disabled) a.page-link');
                  if (nextButton) nextButton.click();
                }
              }
            }, pageNumber);
            
            await delay(1000);
          }
        } catch (error) {
          console.error('Error returning to search results:', error);
          throw error; // Rethrow to abort the page processing
        }
      }
    }
  } catch (error) {
    console.error(`Error processing page ${pageNumber}:`, error);
    throw error;
  }
}

// Save courses to file
function saveCoursesToFile(courses, currentPage = null) {
  try {
    const meta = {
      total_count: courses.length,
      languages: ['ja', 'en'],
      generated_date: new Date().toISOString()
    };
    
    if (currentPage) {
      meta.current_page = currentPage;
    }
    
    const jsonContent = JSON.stringify({
      courses,
      meta
    }, null, 2);
    
    fs.writeFileSync(config.outputFile, jsonContent, 'utf8');
    console.log(`Saved ${courses.length} courses to file${currentPage ? ` after page ${currentPage}` : ''}`);
    
    return true;
  } catch (error) {
    console.error('Error saving courses to file:', error);
    return false;
  }
}

// Execute the main function
scrapeCourses().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});