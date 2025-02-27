# Rate My Teacher

A web application for rating and reviewing university courses and professors, built with PHP and SQLite.

## Course and Professor Data Import System

This repository includes a system for scraping course and professor data from university websites and importing it into the Rate My Teacher database.

### Features

- Web scraper for university course catalogs
- Import system that prevents duplicates
- Search functionality for professors and courses
- Display of real course and professor data on the homepage
- Detailed professor and course pages with reviews

### Installation

1. Clone this repository to your web server
2. Ensure PHP is installed with SQLite and cURL extensions
3. Set up the SQLite database using the SQL files in the `SQL Database Setup` directory
4. Configure your database connection in `config.php`

### Usage

#### Scraping Course Data

The `course_scraper.php` script will scrape course and professor data from the university website and save it to a JSON file.

```bash
php course_scraper.php
```

By default, this will:
- Fetch course data from the university URL specified in the script
- Extract professor and course information
- Store the data in `database/scraped_courses.json`
- Ensure uniqueness by checking if courses/professors already exist

#### Customizing the Scraper

To adapt the scraper for your specific university website:

1. Open `course_scraper.php` in a text editor
2. Modify the `$baseUrl` variable to your university's course catalog URL
3. Customize the `scrapeCourses()` function to match your university's website structure
4. Adjust the parsing logic in `simulateCourseScraping()` to extract the correct data

#### Importing Scraped Data

After running the scraper, use the `course_import.php` script to import the data into the database:

```bash
php course_import.php
```

This will:
- Read the JSON data from `database/scraped_courses.json`
- Insert new professors and courses into the database
- Update existing records as needed
- Ensure proper relationships between courses and professors

#### Updating the Homepage

To display the imported data on the homepage, run the `update_homepage_lists.php` script:

```bash
php update_homepage_lists.php
```

This will:
- Fetch the top-rated professors and courses from the database
- Generate HTML for these items
- Update the `home.php` file to display this real data

### Search Functionality

The search functionality in `search.php` allows users to search for professors and courses by name, department, or course code. The search results are displayed in real-time using AJAX.

### API Endpoints

- `search.php` - Search API for professors and courses
- `professor_details.php` - API for detailed professor information
- `course_details.php` - API for detailed course information

### Common Issues

- If you encounter permission issues, ensure the web server has write access to the database directory
- For large university catalogs, you may need to increase PHP's memory limit and execution time

### Customization

- To change the appearance of search results, modify the CSS styles in `home.php`
- To adjust how ratings are displayed, edit the `generateStarRating()` function in `home.php`

### Credits

- Created by Your Name
- Inspired by Rate My Professor