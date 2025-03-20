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
  maxPagesPerUrl: 20,
  delay: 2000 // Delay between page requests in ms
};

// Helper function for delays
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Main scraping function
async function scrapeCourses() {
  console.log('Starting Keio course scraper...');

  const browser = await puppeteer.launch({ 
    headless: false, // Set to true for production
    defaultViewport: null,
    slowMo: 100
  });

  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(30000);

    // Handle logging
    page.on('console', msg => console.log('Browser console:', msg.text()));

    // Login to the system
    await login(page);

    // Load existing courses if any
    let existingCourses = [];
    if (fs.existsSync(config.outputFile)) {
      try {
        const jsonData = fs.readFileSync(config.outputFile, 'utf8');
        const parsedData = JSON.parse(jsonData);
        if (parsedData.courses && Array.isArray(parsedData.courses)) {
          existingCourses = parsedData.courses;
          console.log(`Loaded ${existingCourses.length} existing courses from file.`);
        }
      } catch (error) {
        console.error('Error loading existing courses:', error);
      }
    }

    // Define the years and fields to scrape
    const yearsToScrape = ['2025', '2024', '2023'];
    const fieldsToScrape = ['基盤科目', '先端科目', '特設科目'];

    // Process each year and field
    for (const year of yearsToScrape) {
      for (const field of fieldsToScrape) {
        console.log(`Scraping year: ${year}, field: ${field}`);
        const courses = await processYearAndField(page, year, field, browser);
        allCourses.push(...courses);
      }
    }

    // Save results
    const meta = {
      total_count: allCourses.length,
      languages: ['ja', 'en'],
      generated_date: new Date().toISOString()
    };

    const jsonContent = JSON.stringify({
      courses: allCourses,
      meta: meta
    }, null, 2);

    fs.writeFileSync(config.outputFile, jsonContent, 'utf8');
    console.log(`Saved ${allCourses.length} courses to ${config.outputFile}`);

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
    await page.goto('https://gslbs.keio.jp/syllabus/search');
    await delay(2000);
    console.log(`Redirected to: ${page.url()}`);

    // STEP 1: HANDLE USERNAME SCREEN
    console.log('Looking for username field...');
    await page.waitForSelector('input[type="text"]', { visible: true });
    console.log('Username field found, entering username');
    await page.type('input[type="text"]', config.credentials.username);

    // Try to find the Next button using multiple approaches
    console.log('Looking for Next button...');
    const buttonSelectors = [
      'button',
      'input[type="submit"]',
      '.button-primary', 
      'button[type="submit"]',
      'button.button'
    ];

    let nextButtonFound = false;

    for (const selector of buttonSelectors) {
      const button = await page.$(selector);
      if (button) {
        console.log(`Found Next button with selector: ${selector}`);
        await button.click();
        nextButtonFound = true;
        break;
      }
    }

    // Method 2: If Method 1 fails, try finding by text content using JavaScript
    if (!nextButtonFound) {
      console.log('Trying to find Next button by text content...');
      const clickedNextButton = await page.evaluate(() => {
        const possibleButtons = [
          ...document.querySelectorAll('button'),
          ...document.querySelectorAll('input[type="submit"]'),
          ...document.querySelectorAll('.button'),
          ...document.querySelectorAll('[role="button"]')
        ];

        const nextButton = Array.from(possibleButtons).find(el => 
          el.textContent.includes('Next') || 
          el.value === 'Next' ||
          el.innerText.includes('Next'));

        if (nextButton) {
          nextButton.click();
          return true;
        }
        return false;
      });

      if (clickedNextButton) {
        console.log('Found and clicked Next button via JavaScript');
        nextButtonFound = true;
      }
    }

    if (!nextButtonFound) {
      throw new Error('Could not find Next button');
    }

    // Wait for password page
    console.log('Waiting for password page...');
    await delay(5000);

    // Try to find password field
    console.log('Looking for password field...');
    const passwordField = await page.$('input[type="password"]');

    if (passwordField) {
      console.log('Password field found, entering password');
      await page.type('input[type="password"]', config.credentials.password);

      // Click verify button using same multiple-approach strategy
      console.log('Looking for Verify button...');
      let verifyClicked = false;

      for (const selector of buttonSelectors) {
        const verifyButton = await page.$(selector);
        if (verifyButton) {
          console.log(`Found Verify button with selector: ${selector}`);
          await verifyButton.click();
          verifyClicked = true;
          console.log('Clicked Verify button');
          break;
        }
      }

      if (!verifyClicked) {
        throw new Error('Could not find Verify button');
      }

      // Wait for login completion
      console.log('Waiting for login to complete...');
      await delay(10000);
      console.log(`Current URL after login: ${page.url()}`);

      // Check if login was successful
      if (!page.url().includes('gslbs.keio.jp')) {
        throw new Error('Login failed. Could not reach syllabus page.');
      }

      console.log('Login successful!');
    } else {
      throw new Error('Password field not found');
    }

  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
}

// Process a specific year and field
async function processYearAndField(page, year, field, browser) {
  console.log(`Processing year: ${year}, field: ${field}`);

  const allCourses = [];

  // Navigate to the search page
  await page.goto('https://gslbs.keio.jp/syllabus/search');
  await delay(2000);

  // Set year to the specified year if the dropdown exists
  const yearSelector = 'select[name="KEYWORD_TTBLYR"]';
  if (await page.$(yearSelector)) {
    await page.select(yearSelector, year);
    console.log(`Selected year: ${year}`);
  }

  // Select the field from the 分野 dropdown using the correct selector
  console.log(`Selecting "${field}" from 分野 dropdown...`);
  const fieldSelector = 'select[name="KEYWORD_FLD1CD"]';
  if (await page.$(fieldSelector)) {
    await page.select(fieldSelector, field);
    console.log(`Selected field: ${field}`);
  } else {
    console.log('Field selector not found');
  }

  // Uncheck the 3rd year checkbox if it exists
  console.log('Unchecking 3rd year checkbox...');
  const yearLevelSelector = 'input[name="KEYWORD_LVL"][value="3"]';
  const yearLevelCheckbox = await page.$(yearLevelSelector);
  if (yearLevelCheckbox) {
    // Make sure the checkbox is unchecked
    const isChecked = await page.evaluate(el => el.checked, yearLevelCheckbox);
    if (isChecked) {
      await yearLevelCheckbox.click();
    }
    console.log('3rd year checkbox unchecked');
  } else {
    console.log('3rd year checkbox not found');
  }

  // Submit the search form
  console.log('Submitting search form...');
  const searchButtonSelector = 'button[data-action_id="SYLLABUS_SEARCH_KEYWORD_EXECUTE"]';
  const searchButton = await page.$(searchButtonSelector);
  if (searchButton) {
    await Promise.all([
      searchButton.click(),
      page.waitForNavigation({ waitUntil: 'networkidle2' })
    ]).catch(e => {
      console.log('Navigation after search button click failed:', e.message);
    });
    console.log('Search button clicked');
  } else {
    // Try to find the search button by text
    console.log('Using text content to find search button...');
    await page.evaluate(() => {
      const buttons = Array.from(document.querySelectorAll('button'));
      const searchBtn = buttons.find(b => 
        b.textContent.includes('検索') || 
        b.textContent.includes('Search'));
      if (searchBtn) searchBtn.click();
    });

    await page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {
      console.log('Navigation timeout, continuing anyway...');
    });
  }

  // Process the search results page
  console.log('Processing search results page...');

  let hasNextPage = true;
  while (hasNextPage) {
    // Wait for the course results to load dynamically
    console.log('Waiting for course results to load...');
    await page.waitForSelector('.btn.btn-info.btn-sm.syllabus-detail.slbs-btn-1', { timeout: 30000 }).catch(() => {
      console.log('No course results found on this page.');
      return allCourses;
    });

    // Set "表示順" to "科目名順" AFTER the course results have loaded
    console.log('Setting 表示順 to 科目名順...');
    const displayOrderSelector = 'select[name="SEARCH_RESULT_NARABIJUN"]';

    try {
      // Wait for the dropdown to be visible
      await page.waitForSelector(displayOrderSelector, { visible: true, timeout: 5000 });

      // Select the option with value "2" (科目名順)
      await page.select(displayOrderSelector, '2');
      console.log('Set 表示順 to 科目名順');
    } catch (error) {
      console.log('表示順 dropdown not found or not visible within 5 seconds');
    }

    // Get all syllabus detail links
    const syllabusLinks = await page.$$('.btn.btn-info.btn-sm.syllabus-detail.slbs-btn-1');
    console.log(`Found ${syllabusLinks.length} syllabus links on the results page.`);

    for (let i = 0; i < syllabusLinks.length; i++) {
      const syllabusLink = syllabusLinks[i];

      // Open the syllabus details in a new tab
      const syllabusUrl = await page.evaluate(link => link.href, syllabusLink);
      const detailPage = await browser.newPage();
      await detailPage.goto(syllabusUrl, { waitUntil: 'networkidle2' });

      console.log(`Navigated to course detail page: ${detailPage.url()}`);

      // Extract Japanese details
      const japaneseDetails = await detailPage.evaluate(() => {
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

      console.log('Extracted Japanese details:', japaneseDetails);

      // Switch to English version of the page
      const englishLink = await detailPage.$('a[href*="lang=en"]');
      if (englishLink) {
        await englishLink.click();
        await detailPage.waitForNavigation({ waitUntil: 'networkidle2' });

        // Extract English details
        const englishDetails = await detailPage.evaluate(() => {
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

        console.log('Extracted English details:', englishDetails);

        // Create the course object
        const course = {
          course_id: japaneseDetails.course_id || englishDetails.course_id,
          year: year,
          semester: 'spring',
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
          ]
        };

        // Add to our collection
        allCourses.push(course);
        console.log(`Added course: ${course.translations.ja.name}`);
      }

      // Close the detail page
      await detailPage.close();

      // Add a small delay between courses to avoid overloading the server
      await delay(1000);
    }

    // Check if there is a next page
    const nextPageButton = await page.$('a[data-action_id="SYLLABUS_SEARCH_RESULT_NEXT_PAGE"]');
    if (nextPageButton) {
      console.log('Navigating to the next page...');
      await nextPageButton.click();
      await page.waitForNavigation({ waitUntil: 'networkidle2' });
    } else {
      hasNextPage = false;
      console.log('No more pages to scrape.');
    }
  }

  return allCourses;
}

// Execute the main function
scrapeCourses().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});