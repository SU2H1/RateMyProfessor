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
          semester: 'spring', // Adjust this based on actual semester data
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
          available_years: [year] // Initialize with the current year
        };

        // Check if the course already exists
        const existingCourse = findExistingCourse(allCourses, course);

        if (existingCourse) {
          // Merge the available_years
          if (!existingCourse.available_years.includes(year)) {
            existingCourse.available_years.push(year);
            existingCourse.available_years.sort(); // Optional: Sort years
            console.log(`Merged course: ${course.translations.ja.name} for year ${year}`);
          }
        } else {
          // Add the new course to the collection
          allCourses.push(course);
          console.log(`Added new course: ${course.translations.ja.name}`);
        }
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