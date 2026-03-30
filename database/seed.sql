USE scholargrid;

SET NAMES utf8mb4;

-- Seeded admin login
-- Email: admin@scholargrid.local
-- Password: Admin@12345

INSERT INTO admins (
    name_cipher,
    email_cipher,
    email_hash,
    password_hash,
    status,
    created_at,
    updated_at
) VALUES (
    'r8+ZOIPQEwA9uT3s8NfTlGnfBCHGrBwobFvI8jMA+UaiSK7rnq6dmiJPJFOD2jyo/lDGKlJ8X3R6r5QRPaFDfJ/luy+mrhfv4vuC9PEeBT0=',
    'IBvhaeGlfe/yrH2+6lmhntKptOnG/zSQIPbpoHcy6pfHpTuthqKzaUPMpRDqUBwd5MndO24ONqAxa/BiVVsVxM0LERVKcWhektQGH3XPRnw=',
    'aad424048f3d120134c24fe7b52a77d18f9fec8be5d1a2e16cd073c2703e62e7',
    'pbkdf2_sha256$600000$iQzzixagKqMMYFM+Q0T5cQ==$lE8Pu6152Oe6Cp1W2xkTS5k5Jtacz9u2j2I695xhSIc=',
    'active',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    name_cipher = VALUES(name_cipher),
    email_cipher = VALUES(email_cipher),
    password_hash = VALUES(password_hash),
    status = VALUES(status),
    updated_at = NOW();

INSERT INTO courses (
    title,
    slug,
    level,
    short_description,
    content_html,
    estimated_minutes,
    created_at,
    updated_at
) VALUES
(
    'HTML',
    'html',
    'Beginner',
    'Learn page structure, semantic tags, forms, media, and document layout with readable examples.',
    '<h2>HTML Fundamentals</h2><p>HTML defines the structure of every web page. It tells the browser what is a heading, paragraph, list, table, form, or section. Clean HTML makes CSS and JavaScript much easier to maintain.</p><h3>Semantic Structure</h3><p>Prefer semantic tags such as header, main, section, article, nav, and footer. These elements improve readability and help assistive technologies understand the page.</p><pre><code>&lt;main&gt;\n  &lt;section&gt;\n    &lt;h1&gt;Welcome&lt;/h1&gt;\n    &lt;p&gt;Readable content lives here.&lt;/p&gt;\n  &lt;/section&gt;\n&lt;/main&gt;</code></pre><h3>Forms and Media</h3><p>HTML also handles user input through forms, plus images, audio, video, and embedded content. Group related fields with labels and fieldsets so forms stay usable and accessible.</p><ul><li>Use labels for every input.</li><li>Choose semantic tags before generic div blocks.</li><li>Keep document structure predictable.</li></ul>',
    28,
    NOW(),
    NOW()
),
(
    'CSS',
    'css',
    'Beginner',
    'Style pages with selectors, layout systems, colors, spacing, typography, and responsive design.',
    '<h2>CSS Fundamentals</h2><p>CSS controls presentation. It turns plain markup into a polished interface by handling layout, spacing, color, typography, transitions, and responsive behavior.</p><h3>The Cascade</h3><p>Specificity, source order, and inheritance decide which rule wins. Understanding the cascade prevents hard to trace styling bugs.</p><pre><code>.card {\n  padding: 1.5rem;\n  border-radius: 1rem;\n  background: white;\n}</code></pre><h3>Modern Layout</h3><p>Flexbox is ideal for one dimensional alignment. Grid is ideal for two dimensional page and card layouts. Media queries adapt the design to smaller screens.</p><ul><li>Define spacing and color variables.</li><li>Use relative units where possible.</li><li>Build mobile friendly layouts from the start.</li></ul>',
    30,
    NOW(),
    NOW()
),
(
    'JavaScript',
    'javascript',
    'Intermediate',
    'Understand variables, functions, arrays, DOM scripting, events, asynchronous code, and browser logic.',
    '<h2>JavaScript Fundamentals</h2><p>JavaScript adds behavior to web pages. It reads user actions, updates the DOM, fetches data, validates forms, and coordinates dynamic interactions.</p><h3>Core Syntax</h3><p>Start with variables, conditions, loops, functions, arrays, and objects. Then move into event handling and asynchronous flows such as promises and async functions.</p><pre><code>const button = document.querySelector("button");\nbutton.addEventListener("click", () =&gt; {\n  console.log("Clicked");\n});</code></pre><h3>DOM and Events</h3><p>The DOM lets JavaScript query elements, change content, toggle classes, and respond to user actions. Good JavaScript stays focused on clear state transitions.</p><ul><li>Prefer readable functions over clever shortcuts.</li><li>Handle events close to the UI they affect.</li><li>Validate input on both client and server.</li></ul>',
    35,
    NOW(),
    NOW()
),
(
    'Python',
    'python',
    'Beginner',
    'Cover syntax, variables, functions, loops, modules, file handling, and practical scripting patterns.',
    '<h2>Python Fundamentals</h2><p>Python is widely used for scripting, automation, web development, data analysis, and machine learning. Its syntax is readable, which makes it ideal for beginners.</p><h3>Basic Building Blocks</h3><p>Learn variables, data types, conditions, loops, functions, lists, tuples, dictionaries, and modules. Indentation is part of the syntax, so spacing matters.</p><pre><code>def greet(name):\n    return f"Hello, {name}"\n\nprint(greet("Student"))</code></pre><h3>Practical Work</h3><p>Python is strong for automation and data processing. Once the basics are clear, move into files, packages, virtual environments, and project organization.</p><ul><li>Write small reusable functions.</li><li>Name variables clearly.</li><li>Practice with short scripts before larger apps.</li></ul>',
    34,
    NOW(),
    NOW()
),
(
    'Machine Learning',
    'machine-learning',
    'Intermediate',
    'Study datasets, features, models, training, evaluation metrics, and workflow fundamentals.',
    '<h2>Machine Learning Basics</h2><p>Machine learning uses data to build systems that recognize patterns and make predictions. A typical workflow includes collecting data, preparing features, training a model, and evaluating results.</p><h3>Model Pipeline</h3><p>Separate training, validation, and test data. Choose metrics that fit the task such as accuracy, precision, recall, or mean squared error.</p><pre><code>data -&gt; preprocess -&gt; train -&gt; evaluate -&gt; improve</code></pre><h3>Common Cautions</h3><p>Avoid data leakage, watch for overfitting, and measure generalization on unseen examples. Strong ML work is as much about data quality as model choice.</p><ul><li>Understand the problem before choosing an algorithm.</li><li>Keep feature engineering consistent.</li><li>Use evaluation metrics that match the goal.</li></ul>',
    40,
    NOW(),
    NOW()
),
(
    'DSA',
    'dsa',
    'Intermediate',
    'Master arrays, linked lists, stacks, queues, trees, graphs, recursion, sorting, and searching.',
    '<h2>Data Structures and Algorithms</h2><p>DSA teaches how to organize data and reason about performance. It is essential for problem solving, interviews, and writing efficient software.</p><h3>Data Structures</h3><p>Arrays, linked lists, stacks, queues, hash maps, trees, heaps, and graphs all solve different storage and access problems.</p><pre><code>Time complexity examples:\nArray access: O(1)\nBinary search: O(log n)\nNested loops: often O(n^2)</code></pre><h3>Algorithm Thinking</h3><p>Focus on tradeoffs, edge cases, and complexity. A correct algorithm with predictable performance is usually better than a short but unclear solution.</p><ul><li>Trace small inputs by hand.</li><li>Understand recursion and iteration tradeoffs.</li><li>Practice explaining why an approach works.</li></ul>',
    42,
    NOW(),
    NOW()
),
(
    'C',
    'c',
    'Intermediate',
    'Learn procedural programming, memory basics, pointers, functions, arrays, and low level control.',
    '<h2>C Programming Basics</h2><p>C is a foundational programming language that gives direct control over memory and execution. Many operating systems, compilers, and embedded systems rely on it.</p><h3>Core Syntax</h3><p>Understand variables, functions, loops, arrays, pointers, and header files. C rewards precision because there is less runtime protection than in higher level languages.</p><pre><code>#include &lt;stdio.h&gt;\n\nint main(void) {\n  printf("Hello, C\\n");\n  return 0;\n}</code></pre><h3>Memory Awareness</h3><p>Use pointers carefully, especially when working with arrays and dynamic memory. Strong C programs are disciplined about initialization, bounds, and cleanup.</p><ul><li>Keep function responsibilities narrow.</li><li>Check pointer logic carefully.</li><li>Compile often and test incrementally.</li></ul>',
    36,
    NOW(),
    NOW()
),
(
    'C++',
    'cpp',
    'Intermediate',
    'Explore classes, objects, STL containers, algorithms, memory management, and modern C++ style.',
    '<h2>C++ Fundamentals</h2><p>C++ extends C with abstraction, object oriented programming, templates, and a large standard library. It can be used for systems programming, games, desktop apps, and performance critical tooling.</p><h3>Language Features</h3><p>Study classes, constructors, inheritance, templates, references, and the Standard Template Library. Modern C++ emphasizes RAII and safer ownership patterns.</p><pre><code>#include &lt;iostream&gt;\nint main() {\n  std::cout &lt;&lt; "Hello, C++";\n}</code></pre><h3>Practical Style</h3><p>Prefer standard containers and smart pointers where possible. Express ownership clearly and avoid manual memory work when a standard abstraction fits.</p><ul><li>Use vectors before raw dynamic arrays.</li><li>Prefer readable class design.</li><li>Learn the STL early.</li></ul>',
    38,
    NOW(),
    NOW()
),
(
    'Ethical Hacking',
    'ethical-hacking',
    'Intermediate',
    'Understand security testing fundamentals, reconnaissance, vulnerabilities, reporting, and legal boundaries.',
    '<h2>Ethical Hacking Basics</h2><p>Ethical hacking focuses on finding and reporting security weaknesses with authorization. It combines technical skill with legal and professional responsibility.</p><h3>Typical Workflow</h3><p>Security testing often includes reconnaissance, vulnerability validation, exploitation in approved scope, and clear remediation reporting.</p><pre><code>Scope -&gt; Recon -&gt; Validate -&gt; Report -&gt; Remediate</code></pre><h3>Responsible Practice</h3><p>Never test systems without permission. Good security work documents findings precisely and explains business impact and fixes.</p><ul><li>Stay inside the approved scope.</li><li>Log findings clearly.</li><li>Focus on defense, not theatrics.</li></ul>',
    37,
    NOW(),
    NOW()
),
(
    'Linux',
    'linux',
    'Beginner',
    'Build command line fluency with files, permissions, processes, networking, and package management.',
    '<h2>Linux Basics</h2><p>Linux powers servers, cloud environments, developer workstations, and containers. Working comfortably in a shell is a major productivity skill.</p><h3>Core Commands</h3><p>Start with navigation, file management, permissions, process inspection, text viewing, and package management. Learn how commands fit together through pipelines.</p><pre><code>pwd\nls -la\ncd /var/www\ncat file.txt</code></pre><h3>System Thinking</h3><p>Understand users, groups, services, logs, and networking basics. Linux becomes easier once you see it as a set of small composable tools.</p><ul><li>Read command help often.</li><li>Practice file permissions.</li><li>Use logs to diagnose problems.</li></ul>',
    32,
    NOW(),
    NOW()
),
(
    'Django',
    'django',
    'Intermediate',
    'Create Python web applications with models, views, templates, routing, forms, and admin tools.',
    '<h2>Django Fundamentals</h2><p>Django is a Python web framework that ships with routing, ORM models, templates, authentication, forms, and a strong admin interface.</p><h3>MVT Structure</h3><p>Django organizes projects around models, views, and templates. URLs map requests to views, views coordinate data, and templates render HTML.</p><pre><code>python manage.py startproject scholargrid\npython manage.py startapp courses</code></pre><h3>ORM and Admin</h3><p>The ORM maps Python classes to database tables. Django admin speeds up internal management tasks and works well for content oriented systems.</p><ul><li>Keep apps focused by responsibility.</li><li>Use migrations carefully.</li><li>Separate business logic from templates.</li></ul>',
    39,
    NOW(),
    NOW()
),
(
    'MySQL',
    'mysql',
    'Beginner',
    'Study relational databases, tables, joins, indexes, constraints, and query writing in MySQL.',
    '<h2>MySQL Fundamentals</h2><p>MySQL stores structured relational data using tables, keys, constraints, and SQL queries. It is common in web applications and reporting systems.</p><h3>Essential Concepts</h3><p>Learn how rows, columns, primary keys, foreign keys, joins, indexes, and transactions work together. Good schema design keeps data consistent and queries efficient.</p><pre><code>SELECT title, level\nFROM courses\nORDER BY title ASC;</code></pre><h3>Query Discipline</h3><p>Write readable SQL, use indexes for lookup paths, and protect sensitive information with hashing and encryption where appropriate.</p><ul><li>Choose clear key relationships.</li><li>Use transactions for multi step writes.</li><li>Index the columns you filter often.</li></ul>',
    33,
    NOW(),
    NOW()
),
(
    'MongoDB',
    'mongodb',
    'Beginner',
    'Learn document databases, collections, schema design, indexing, and querying with MongoDB.',
    '<h2>MongoDB Basics</h2><p>MongoDB stores data as flexible JSON like documents inside collections. It is useful when data shapes vary or evolve quickly.</p><h3>Documents and Collections</h3><p>Each document can contain nested fields and arrays, which makes MongoDB expressive for hierarchical data. Good schema design still matters even without fixed table structures.</p><pre><code>{\n  "title": "HTML",\n  "level": "Beginner",\n  "topics": ["tags", "forms", "semantics"]\n}</code></pre><h3>Indexes and Queries</h3><p>Indexes improve read performance, but they add write cost. Model around your query patterns rather than around raw convenience.</p><ul><li>Design for your most important reads.</li><li>Use indexes intentionally.</li><li>Keep document size reasonable.</li></ul>',
    31,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    level = VALUES(level),
    short_description = VALUES(short_description),
    content_html = VALUES(content_html),
    estimated_minutes = VALUES(estimated_minutes),
    updated_at = NOW();

DELETE tq
FROM test_questions tq
INNER JOIN courses c ON c.id = tq.course_id
WHERE c.slug IN (
    'html',
    'css',
    'javascript',
    'python',
    'machine-learning',
    'dsa',
    'c',
    'cpp',
    'ethical-hacking',
    'linux',
    'django',
    'mysql',
    'mongodb'
);

INSERT INTO test_questions (
    course_id,
    difficulty,
    question_text,
    option_a,
    option_b,
    option_c,
    option_d,
    correct_option,
    explanation,
    status,
    created_at,
    updated_at
)
SELECT
    c.id,
    d.difficulty,
    CONCAT(c.title, ' ', UPPER(LEFT(d.difficulty, 1)), SUBSTRING(d.difficulty, 2), ' Question ', LPAD(sequence_numbers.n, 2, '0'), ': choose the most accurate statement.'),
    CASE WHEN MOD(sequence_numbers.n - 1, 4) = 0
        THEN CONCAT('It describes a core ', c.title, ' principle covered in the lesson.')
        ELSE CONCAT('It ignores the main ', c.title, ' workflow explained in the course.')
    END,
    CASE WHEN MOD(sequence_numbers.n - 1, 4) = 1
        THEN CONCAT('It describes a core ', c.title, ' principle covered in the lesson.')
        ELSE CONCAT('It removes structure and best practice from ', c.title, '.')
    END,
    CASE WHEN MOD(sequence_numbers.n - 1, 4) = 2
        THEN CONCAT('It describes a core ', c.title, ' principle covered in the lesson.')
        ELSE CONCAT('It contradicts the practical use of ', c.title, ' in a real project.')
    END,
    CASE WHEN MOD(sequence_numbers.n - 1, 4) = 3
        THEN CONCAT('It describes a core ', c.title, ' principle covered in the lesson.')
        ELSE CONCAT('It treats ', c.title, ' as unrelated to syntax, logic, or implementation.')
    END,
    ELT(MOD(sequence_numbers.n - 1, 4) + 1, 'A', 'B', 'C', 'D'),
    CONCAT('Review the ', c.title, ' course section for concept ', sequence_numbers.n, ' at the ', d.difficulty, ' level.'),
    1,
    NOW(),
    NOW()
FROM courses c
CROSS JOIN (
    SELECT 'beginner' AS difficulty
    UNION ALL SELECT 'intermediate'
    UNION ALL SELECT 'advanced'
) AS d
CROSS JOIN (
    SELECT 1 AS n
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
    UNION ALL SELECT 8
    UNION ALL SELECT 9
    UNION ALL SELECT 10
    UNION ALL SELECT 11
    UNION ALL SELECT 12
    UNION ALL SELECT 13
    UNION ALL SELECT 14
    UNION ALL SELECT 15
    UNION ALL SELECT 16
    UNION ALL SELECT 17
    UNION ALL SELECT 18
    UNION ALL SELECT 19
    UNION ALL SELECT 20
) AS sequence_numbers;
