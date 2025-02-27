// Simple hardcoded search implementation for demonstration
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const searchResults = document.getElementById('searchResults');
    
    // Debug - log if elements are found
    console.log("Search input found:", !!searchInput);
    console.log("Search button found:", !!searchButton);
    console.log("Search results found:", !!searchResults);
    
    if (!searchInput || !searchButton || !searchResults) {
        console.error("Search elements not found");
        return;
    }
    
    // Make sure the search results div is properly styled
    searchResults.style.position = 'absolute';
    searchResults.style.top = '100%';
    searchResults.style.left = '0';
    searchResults.style.width = '100%';
    searchResults.style.backgroundColor = 'white';
    searchResults.style.border = '1px solid #ddd';
    searchResults.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
    searchResults.style.zIndex = '1000';
    
    // Sample data with English and Japanese versions
    const data = {
        professors: [
            { id: 1, name: "Jin Mitsugi", name_ja: "三次 仁", department: "Data Science", department_ja: "データサイエンス" },
            { id: 2, name: "Tomoki Kamo", name_ja: "加茂 具樹", department: "Policy Management", department_ja: "政策管理学" },
            { id: 3, name: "Takumi Shimizu", name_ja: "清水 匠", department: "Policy Management", department_ja: "政策管理学" },
            { id: 4, name: "Tate Kihara", name_ja: "木原 盾", department: "Policy Management", department_ja: "政策管理学" },
            { id: 5, name: "Haruo Suzuki", name_ja: "鈴木 治夫", department: "Environment and Information", department_ja: "環境情報学" },
            { id: 6, name: "Hiroya Tanaka", name_ja: "田中 浩也", department: "Environment and Information", department_ja: "環境情報学" }
        ],
        courses: [
            { id: 1, name: "Data Science DS1", name_ja: "データサイエンス DS1", course_code: "01727" },
            { id: 2, name: "POLICY MANAGEMENT STUDIES", name_ja: "政策管理研究", course_code: "01727" },
            { id: 3, name: "ENVIRONMENT AND INFORMATION STUDIES", name_ja: "環境情報学研究", course_code: "01674" }
        ]
    };
    
    // Get current language from cookie
    function getCurrentLanguage() {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith('language=')) {
                return cookie.substring('language='.length);
            }
        }
        return 'en'; // Default to English
    }
    
    // Search function
    function performSearch(query) {
        query = query.toLowerCase();
        const lang = getCurrentLanguage();
        const isJapanese = lang === 'ja';
        
        // Filter professors
        const matchingProfessors = data.professors.filter(prof => {
            if (isJapanese) {
                return (prof.name_ja && prof.name_ja.toLowerCase().includes(query)) || 
                       (prof.department_ja && prof.department_ja.toLowerCase().includes(query));
            } else {
                return prof.name.toLowerCase().includes(query) || 
                       prof.department.toLowerCase().includes(query);
            }
        });
        
        // Filter courses
        const matchingCourses = data.courses.filter(course => {
            if (isJapanese) {
                return (course.name_ja && course.name_ja.toLowerCase().includes(query)) || 
                       course.course_code.toLowerCase().includes(query);
            } else {
                return course.name.toLowerCase().includes(query) || 
                       course.course_code.toLowerCase().includes(query);
            }
        });
        
        return {
            professors: matchingProfessors,
            courses: matchingCourses
        };
    }
    
    // Display results
    function displayResults(results) {
        searchResults.innerHTML = ''; // Clear previous results
        const lang = getCurrentLanguage();
        const isJapanese = lang === 'ja';
        
        if (results.professors.length === 0 && results.courses.length === 0) {
            searchResults.innerHTML = '<div class="no-results" style="padding:15px; text-align:center;">' + 
                                     (isJapanese ? '検索結果がありません' : 'No results found') + '</div>';
            return;
        }
        
        // Add professors
        if (results.professors.length > 0) {
            const profSection = document.createElement('div');
            profSection.style.padding = '10px';
            profSection.innerHTML = `<h3 style="margin:5px 0; font-size:16px;">${isJapanese ? '教授' : 'Professors'} (${results.professors.length})</h3>`;
            
            const profList = document.createElement('div');
            results.professors.forEach(prof => {
                const item = document.createElement('div');
                item.className = 'search-item';
                item.style.padding = '8px 10px';
                item.style.margin = '5px 0';
                item.style.backgroundColor = '#f9f9f9';
                item.style.borderRadius = '4px';
                item.style.cursor = 'pointer';
                item.innerHTML = `
                    <div>
                        <strong>${isJapanese && prof.name_ja ? prof.name_ja : prof.name}</strong>
                        <div style="color:#666; font-size:13px;">${isJapanese && prof.department_ja ? prof.department_ja : prof.department}</div>
                    </div>
                `;
                item.addEventListener('mouseover', function() {
                    this.style.backgroundColor = '#f0f4ff';
                });
                item.addEventListener('mouseout', function() {
                    this.style.backgroundColor = '#f9f9f9';
                });
                item.addEventListener('click', function() {
                    // Get professor name with no spaces for URL parameter
                    const profNameForUrl = prof.name.replace(/\s+/g, '');
                    // Save the URL we're redirecting to for debugging
                    const url = `professor.php?id=${prof.id}&professor=${profNameForUrl}&lang=${lang}`;
                    console.log("Redirecting to professor page:", url);
                    window.location.href = url;
                });
                profList.appendChild(item);
            });
            
            profSection.appendChild(profList);
            searchResults.appendChild(profSection);
        }
        
        // Add courses
        if (results.courses.length > 0) {
            const courseSection = document.createElement('div');
            courseSection.style.padding = '10px';
            courseSection.innerHTML = `<h3 style="margin:5px 0; font-size:16px;">${isJapanese ? 'コース' : 'Courses'} (${results.courses.length})</h3>`;
            
            const courseList = document.createElement('div');
            results.courses.forEach(course => {
                const item = document.createElement('div');
                item.className = 'search-item';
                item.style.padding = '8px 10px';
                item.style.margin = '5px 0';
                item.style.backgroundColor = '#f9f9f9';
                item.style.borderRadius = '4px';
                item.style.cursor = 'pointer';
                item.innerHTML = `
                    <div>
                        <strong>${isJapanese && course.name_ja ? course.name_ja : course.name}</strong>
                        <div style="color:#666; font-size:13px;">${isJapanese ? 'コース番号' : 'Course Code'}: ${course.course_code}</div>
                    </div>
                `;
                item.addEventListener('mouseover', function() {
                    this.style.backgroundColor = '#f0f4ff';
                });
                item.addEventListener('mouseout', function() {
                    this.style.backgroundColor = '#f9f9f9';
                });
                item.addEventListener('click', function() {
                    // Get course name and encode for URL
                    const courseNameEncoded = encodeURIComponent(course.name);
                    // Save the URL we're redirecting to for debugging
                    const url = `course.php?id=${course.id}&course=${courseNameEncoded}&lang=${lang}`;
                    console.log("Redirecting to course page:", url);
                    window.location.href = url;
                });
                courseList.appendChild(item);
            });
            
            courseSection.appendChild(courseList);
            searchResults.appendChild(courseSection);
        }
        
        searchResults.style.display = 'block';
        
        // Add "View all results" link
        const footer = document.createElement('div');
        footer.style.borderTop = '1px solid #eee';
        footer.style.padding = '10px';
        footer.style.textAlign = 'center';
        
        const viewAllLink = document.createElement('a');
        viewAllLink.href = `search.php?q=${encodeURIComponent(searchInput.value.trim())}&lang=${lang}`;
        viewAllLink.style.color = '#1e3a8a';
        viewAllLink.style.textDecoration = 'none';
        viewAllLink.style.fontWeight = 'bold';
        viewAllLink.textContent = isJapanese ? '全ての結果を表示' : 'View all results';
        
        footer.appendChild(viewAllLink);
        searchResults.appendChild(footer);
    }
    
    // Handle input
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        console.log("Input event triggered, query:", query);
        
        if (query.length < 2) {
            console.log("Query too short, hiding results");
            searchResults.style.display = 'none';
            return;
        }
        
        const results = performSearch(query);
        console.log("Search results:", results);
        displayResults(results);
        
        // Force display block after small delay to ensure it's visible
        setTimeout(() => {
            searchResults.style.display = 'block';
            console.log("Dropdown should now be visible:", searchResults.style.display);
        }, 10);
    });
    
    // Handle button click
    searchButton.addEventListener('click', function() {
        const query = searchInput.value.trim();
        console.log("Search button clicked, query:", query);
        
        if (query.length < 2) return;
        
        const results = performSearch(query);
        displayResults(results);
        
        // Force display block
        searchResults.style.display = 'block';
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchButton.contains(e.target) && 
            !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Add a test function to show search is working
    window.testSearch = function(query) {
        searchInput.value = query;
        const event = new Event('input', { bubbles: true });
        searchInput.dispatchEvent(event);
    };
});