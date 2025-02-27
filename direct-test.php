<?php
// Test file for direct access to professor and course pages

// Display the form to test direct access
echo "<h1>Direct URL Test</h1>";
echo "<form action='professor.php' method='get'>
      <h2>Test Professor Page</h2>
      <label>Professor Name: <input type='text' name='name' value='HiroyaTanaka'></label>
      <button type='submit'>Test Professor</button>
      </form>";

echo "<form action='course_page_template.php' method='get'>
      <h2>Test Course Page</h2>
      <label>Professor: <input type='text' name='professor' value='HiroyaTanaka'></label><br>
      <label>Course: <input type='text' name='course' value='ENVIRONMENT AND INFORMATION STUDIES [1st half of semester]'></label><br>
      <label>Year: <input type='text' name='year' value='2024'></label><br>
      <label>Language: <input type='text' name='lang' value='en'></label><br>
      <button type='submit'>Test Course</button>
      </form>";
?>