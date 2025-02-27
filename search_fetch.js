// Test JS file to fetch from search.php
// Save this as a standalone file and open in browser

// Function to fetch search results
function fetchSearchResults(query) {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<p>Searching for: ' + query + '</p>';
    
    fetch(`search.php?query=${encodeURIComponent(query)}&t=${Date.now()}`)
        .then(response => {
            console.log('Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                let html = `<h3>Results for "${query}":</h3>`;
                
                // Professors
                html += `<h4>Professors (${data.professors ? data.professors.length : 0}):</h4>`;
                if (data.professors && data.professors.length > 0) {
                    html += '<ul>';
                    data.professors.forEach(prof => {
                        html += `<li>${prof.name} - ${prof.department}</li>`;
                    });
                    html += '</ul>';
                } else {
                    html += '<p>No professors found</p>';
                }
                
                // Courses
                html += `<h4>Courses (${data.courses ? data.courses.length : 0}):</h4>`;
                if (data.courses && data.courses.length > 0) {
                    html += '<ul>';
                    data.courses.forEach(course => {
                        html += `<li>${course.name} (${course.course_code}) - ${course.professor_name}</li>`;
                    });
                    html += '</ul>';
                } else {
                    html += '<p>No courses found</p>';
                }
                
                // Raw data
                html += '<details><summary>Raw JSON Response</summary><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre></details>';
                
                resultDiv.innerHTML = html;
            } catch (e) {
                resultDiv.innerHTML = `
                    <h3>Error parsing response</h3>
                    <p>${e.message}</p>
                    <pre>${text}</pre>
                `;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<h3>Fetch Error</h3><p>${error.message}</p>`;
            console.error('Fetch error:', error);
        });
}

// Create test HTML
document.body.innerHTML = `
    <h1>Search Test</h1>
    <div style="margin-bottom: 20px;">
        <input type="text" id="searchInput" placeholder="Enter search term..." style="padding: 8px; width: 300px;">
        <button id="searchButton" style="padding: 8px 12px;">Search</button>
    </div>
    <div id="result" style="border: 1px solid #ccc; padding: 15px; margin-top: 20px;"></div>
`;

// Add event listeners
document.getElementById('searchButton').addEventListener('click', () => {
    const query = document.getElementById('searchInput').value.trim();
    if (query) {
        fetchSearchResults(query);
    }
});

document.getElementById('searchInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        document.getElementById('searchButton').click();
    }
});

// Set a default search term
document.getElementById('searchInput').value = 'Introduction';

// Show instructions
document.getElementById('result').innerHTML = `
    <p>Enter a search term and click "Search" to test the search functionality.</p>
    <p>Try these terms:</p>
    <ul>
        <li>Introduction</li>
        <li>Economics</li>
        <li>Tanaka</li>
        <li>CS301</li>
    </ul>
`;