// global array to track all errors across attempts
const form_errors = []; 

// Key used to store project data in localStorage
const LOCAL_PROJECTS_KEY = 'portfolio-projects';

// remote JSON endpoint
const REMOTE_PROJECTS_URL = 'https://api.jsonbin.io/v3/b/6932672aae596e708f84a5d8';

// REMOVE AFTER FALL QUARTER IS FINISHED
const JSONBIN_API_KEY = '$2a$10$lT2qyJJb10RpAyr4zcn90ehI9at3LPo3DJOlMwcu4xWidQUXRQRgy';


// Project data 
const PROJECT_CARDS_DATA = [
    {
        id: 'example-1',
        title: 'Let Me Cook',
        imageSmall: 'images/LetMeCook.png',
        imageLarge: 'images/LetMeCook.png',
        imageAlt: 'A photo of project 1',
        description: 'Let Me Cook is a recipe card website I built with some friends in early 2025. It allows users to save their favorite recipes by storing them locally on the website. I did much of the design for each of the pages including the figma mock ups as well as the HTML and CSS implimentations. It is one of the first coding projects I got to do with a larger group. There were 11 of us working together on the project so it also gave me the opportunity to learn the intricacies communicating effectively and planning with a team. If you\'d like to learn more about this project, please click the link below and visit my GitHub page which explains more in-depth how the website works as well as a live deployment.',
        tags: ['HTML', 'CSS', 'JavaScript'],
        link: 'https://github.com/doorjay/LMC2'
    },
    {
        id: 'example-2',
        title: 'Example Project 2',
        imageSmall: 'images/project2-small.jpg',
        imageLarge: 'images/project2.jpg',
        imageAlt: 'A photo of project 2',
        description: 'A sample project with a short description to demonstrate reusable cards.',
        tags: ['Tag 1', 'Tag 2', 'Tag 3'],
        link: 'projects.html'
    },
    {
        id: 'example-3',
        title: 'Example Project 3',
        imageSmall: 'images/project3-small.jpg',
        imageLarge: 'images/project3.jpg',
        imageAlt: 'A photo of project 3',
        description: 'Another example project that highlights my work on layouts and design.',
        tags: ['Layout', 'Design', 'Practice'], 
        link: ''
    }
];


// Helper to get current projects from localStorage, falling back to defaults
function getLocalProjects()
{
    const stored = localStorage.getItem(LOCAL_PROJECTS_KEY);

    if (!stored)
    {
        try
        {
            localStorage.setItem(LOCAL_PROJECTS_KEY, JSON.stringify(PROJECT_CARDS_DATA));
        }
        catch (error)
        {
            console.error('Error seeding localStorage from defaults: ', error);
        }

        //return a shallow copy so callers do not mutate the og key
        return PROJECT_CARDS_DATA.slice();
    }

    try
    {
        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed))
        {
            return parsed;
        }
    }
    catch (error)
    {
        console.error('Error parsing local projects from storage:', error);
    }

    return [];
}

// Helper to save projects array back to localStorage
function saveLocalProjects(projects)
{
    try
    {
        localStorage.setItem(LOCAL_PROJECTS_KEY, JSON.stringify(projects));
    }
    catch (error)
    {
        console.error('Error saving projects to localStorage:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setupThemeToggle();
    setupViewTransions();
    setupContactFormJS();
    setupProjectsPage();
    setupCrudPage();
}); 

// Contact Form Validation
function setupContactFormJS() {
    const form = document.querySelector('#contact-form'); 
    if (!form) return; // Not on the JS form page

    const nameField = form.querySelector('#name');
    const emailField = form.querySelector('#email'); 
    const messageField = form.querySelector('#message'); 
    const errorOutput = form.querySelector('#error-output');
    const infoOutput = form.querySelector('#info-output');

    // reset output initially
    if (errorOutput) errorOutput.textContent = '';
    if (infoOutput) infoOutput.textContent = '';


    // ERROR LOGGER
    const errorLogField = document.querySelector('#form-errors-field');

    function logError(field, message) {
        form_errors.push({
            field: field.name || field.id,
            value: field.value,
            message: message,
            time: new Date().toISOString()
        });
    }



    // MASKING
    // prevent illegal characters based on pattern
    nameField.addEventListener('input', () => {
        const pattern = new RegExp(nameField.pattern);
        const value = nameField.value;

        // If the whole value no longer matches the allowed pattern
        if (!pattern.test(value)) {
            // Remove the last typed character
            nameField.value = value.slice(0, -1);

            // Flash field (visual feedback)
            nameField.classList.add('field-flash');
            setTimeout(() => {
                nameField.classList.remove('field-flash');
            }, 200);

            logError(nameField, "Illegal character typed");
        }
    });


    // Character count warning in message field
    const charCountSpan = document.querySelector('#char-count');
    const maxChars = messageField.maxLength;

    function updateCharacterCount() {
        const currentLength = messageField.value.length;
        const remaining = maxChars - currentLength;

        // Update the label's inline countdown
        charCountSpan.textContent = `(${remaining} left)`;

        // Warning when near limit
        if (remaining <= 50) {
            charCountSpan.style.color = "crimson";
            charCountSpan.style.fontWeight = "600";
        } else {
            charCountSpan.style.color = "";
            charCountSpan.style.fontWeight = "";
        }

        // Prevent exceeding the limit (copy/paste)
        if (currentLength > maxChars) {
            messageField.value = messageField.value.slice(0, maxChars);
        }
    }

    // Update on input
    messageField.addEventListener('input', updateCharacterCount);

    // Show initial value on load
    updateCharacterCount();


    // VALIDATION
    // Helper to set a custom message based on validity state
    function setMessageForField(field) {
        field.setCustomValidity(''); // clear old message

        if (field === nameField) {
            if (field.validity.valueMissing) {
                field.setCustomValidity('Please enter your name.');
            }
            else if (field.validity.tooShort) {
                field.setCustomValidity('Name must be at least 2 characters long.'); 
            }
        }

        if (field === emailField) {
            if (field.validity.valueMissing) {
                field.setCustomValidity('Email is required.');
            }
            else if (field.validity.tooShort) {
                field.setCustomValidity('Please enter a valid email adress.'); 
            }
        }

        if (field === messageField) {
            if (field.validity.valueMissing) {
                field.setCustomValidity('Please enter a message.');
            }
            else if (field.validity.tooShort) {
                field.setCustomValidity('Message is too short. Please write a bit more.'); 
            }
        }
    }

    // Helper to show the message for the first invalid field in errorOutput
    function showFirstErrorMessage() {
        if (!errorOutput) return;

        // Order matters: name → email → message
        const fields = [nameField, emailField, messageField];

        for (const field of fields) {
            if (!field.checkValidity()) {
                errorOutput.textContent = field.validationMessage;
                return;
            }
        }   

        // If everything is valid, clear the error area
        errorOutput.textContent = '';
    }

    // Validate each field when it loses focus
    [nameField, emailField, messageField].forEach(field => {
        field.addEventListener('blur', () => {
            setMessageForField(field);
            field.checkValidity();
            if (!field.checkValidity()) {
                logError(field, field.validationMessage);
            }
            showFirstErrorMessage();
        });

        // Also respond while typing (so messages go away as they fix issues)
        field.addEventListener('input', () => {
            setMessageForField(field);
            field.checkValidity();
            showFirstErrorMessage();
        });
    });

    // Handle submit
    form.addEventListener('submit', (event) => {
        // Make sure all fields get their custom messages
        [nameField, emailField, messageField].forEach(setMessageForField);

        // If form is invalid, prevent submit and show first error
        if (!form.checkValidity()) {
            event.preventDefault();

            [nameField, emailField, messageField].forEach(field => {
                if (!field.checkValidity()) {
                    logError(field, field.validationMessage);
                }
            });

            showFirstErrorMessage();
        }

        // if form is valid, save error history into hidden field
        if (errorLogField) {
            errorLogField.value = JSON.stringify(form_errors);
        }

    });

}

function setupThemeToggle() {
    const toggle = document.querySelector('#theme-toggle'); 
    if (!toggle) return; 

    const savedTheme = localStorage.getItem('preferred-theme');

    if (savedTheme === 'dark') {
        toggle.checked = true; 
    }
    else if (savedTheme === 'light') {
        toggle.checked = false;
    }

    toggle.addEventListener('change', () => {
        const theme = toggle.checked ? 'dark' : 'light';
        localStorage.setItem('preferred-theme', theme);
    });
}

// Custom element for a single project card
class ProjectCard extends HTMLElement
{
    constructor()
    {
        super();
        this._data = null;
    }

    // Allow setting data via JS: card.data = {...}
    set data(value)
    {
        this._data = value;
        this.render();
    }

    get data()
    {
        return this._data;
    }

    connectedCallback()
    {
        // If data was not set programmatically, allow light attribute usage
        if (!this._data)
        {
            const title = this.getAttribute('title');

            if (title)
            {
                this._data = {
                    id: this.getAttribute('id') || '',
                    title: title,
                    imageSmall: this.getAttribute('image-small') || '',
                    imageLarge: this.getAttribute('image-large') || '',
                    imageAlt: this.getAttribute('image-alt') || '',
                    description: this.textContent.trim(),
                    tags: []
                };
            }
        }

        this.render();
    }

    // Build the card
    render()
    {
        if (!this._data)
        {
            return;
        }

        // Clear existing children
        this.textContent = '';

        const {
            title,
            imageSmall,
            imageLarge,
            imageAlt,
            description,
            tags,
            link
        } = this._data;

        // <article class="project-card">
        const article = document.createElement('article');
        article.classList.add('project-card');

        // <picture> with <source> and <img>
        const picture = document.createElement('picture');

        if (imageSmall || imageLarge)
        {
            const source = document.createElement('source');
            const srcsetParts = [];

            if (imageSmall)
            {
                srcsetParts.push(`${imageSmall} 480w`);
            }

            if (imageLarge)
            {
                srcsetParts.push(`${imageLarge} 800w`);
            }

            if (srcsetParts.length > 0)
            {
                source.setAttribute('srcset', srcsetParts.join(', '));
                source.setAttribute('sizes', '(min-width: 700px) 90vw, 200px');
                source.setAttribute('type', 'image/jpeg');
                picture.appendChild(source);
            }

            const img = document.createElement('img');
            img.src = imageSmall || imageLarge || '';
            img.alt = imageAlt || '';
            img.loading = 'lazy';
            img.decoding = 'async';

            picture.appendChild(img);
        }

        // Text content section 
        const bodySection = document.createElement('section');
        bodySection.classList.add('card-body');

        const heading = document.createElement('h2');
        heading.textContent = title;
        bodySection.appendChild(heading);

        if (description)
        {
            const paragraph = document.createElement('p');
            paragraph.textContent = description;
            bodySection.appendChild(paragraph);
        }

        // “Learn more” link 
        if (typeof link === 'string' && link.trim() !== '')
        {
            const anchor = document.createElement('a');
            anchor.href = link;

            // External links → new tab, internal → same tab
            const trimmed = link.trim();
            const isExternal = trimmed.startsWith('http://') || trimmed.startsWith('https://');

            if (isExternal)
            {
                anchor.target = '_blank';
                anchor.rel = 'noopener';
            }

            anchor.textContent = 'Learn more';
            bodySection.appendChild(anchor);
        }

        // Tags list if provided
        if (Array.isArray(tags) && tags.length > 0)
        {
            const tagList = document.createElement('ul');
            tagList.classList.add('tags');

            tags.forEach((tagText) =>
            {
                const li = document.createElement('li');
                li.textContent = tagText;
                tagList.appendChild(li);
            });

            bodySection.appendChild(tagList);
        }

        // Put everything together in the article
        article.appendChild(picture);
        article.appendChild(bodySection);

        // Attach article to the custom element
        this.appendChild(article);
    }
}

// Register <project-card>
customElements.define('project-card', ProjectCard);

// Render an array of project objects into <project-card> elements
function renderProjectCards(projects)
{
    const cardsSection = document.querySelector('#project-cards');

    if (!cardsSection)
    {
        return;
    }

    // Clear existing children without innerHTML
    while (cardsSection.firstChild)
    {
        cardsSection.removeChild(cardsSection.firstChild);
    }

    // Add one <project-card> per project
    projects.forEach((project) =>
    {
        const card = document.createElement('project-card');
        card.data = project; // uses the setter in ProjectCard
        cardsSection.appendChild(card);
    });
}


function setupProjectsPage() 
{
    const cardsSection = document.querySelector('#project-cards');

    // not on projects page
    if (!cardsSection)
    {
        return;
    }

    const loadLocalButton = document.querySelector('#load-local');
    const loadRemoteButton = document.querySelector('#load-remote');

    // Seed localStorage once with the default data if empty
    if (!localStorage.getItem(LOCAL_PROJECTS_KEY))
    {
        try
        {
            localStorage.setItem(LOCAL_PROJECTS_KEY, JSON.stringify(PROJECT_CARDS_DATA));
        }
        catch (error)
        {
            console.error('Error seeding localStorage:', error);
        }
    }

    // Helper to load from localStorage and render
    function handleLoadLocal()
    {
        const stored = localStorage.getItem(LOCAL_PROJECTS_KEY);

        if (!stored)
        {
            // If somehow missing, fall back to default data
            renderProjectCards(PROJECT_CARDS_DATA);
            return;
        }

        try
        {
            const projects = JSON.parse(stored);
            if (Array.isArray(projects))
            {
                renderProjectCards(projects);
            }
            else
            {
                console.warn('Local projects data is not an array, using defaults.');
                renderProjectCards(PROJECT_CARDS_DATA);
            }
        }
        catch (error)
        {
            console.error('Error parsing local projects from storage:', error);
            renderProjectCards(PROJECT_CARDS_DATA);
        }
    }

    // Helper to fetch from remote endpoint and render
        // Helper to fetch from JSONBin and render
    async function handleLoadRemote()
    {
        try
        {
            const response = await fetch(REMOTE_PROJECTS_URL,
            {
                headers:
                {
                    'X-Master-Key': JSONBIN_API_KEY,
                }
            });

            if (!response.ok)
            {
                throw new Error(`Network response was not ok: ${response.status}`);
            }

            const raw = await response.json();

            // JSONBin v3 usually returns { record: <your data>, metadata: {...} }
            // If X-Bin-Meta: 'false' is honored, raw *may* just be your data.
            const record = raw.record ?? raw;

            // Support either [ {...}, {...} ] or { projects: [ {...} ] }
            const projects = Array.isArray(record) ? record : record.projects;

            if (!Array.isArray(projects))
            {
                console.error('Remote data is not an array and has no "projects" array.');
                return;
            }

            renderProjectCards(projects);
        }
        catch (error)
        {
            console.error('Error loading remote projects:', error);

            const cardsSection = document.querySelector('#project-cards');
            if (!cardsSection)
            {
                return;
            }

            // Clear previous content without innerHTML
            while (cardsSection.firstChild)
            {
                cardsSection.removeChild(cardsSection.firstChild);
            }

            const errorParagraph = document.createElement('p');
            errorParagraph.textContent = 'There was a problem loading remote project data.';
            cardsSection.appendChild(errorParagraph);
        }
    }


    // Attach button handlers (overwrite any previous handlers to avoid double-binding)
    if (loadLocalButton)
    {
        loadLocalButton.onclick = handleLoadLocal;
    }

    if (loadRemoteButton)
    {
        loadRemoteButton.onclick = handleLoadRemote;
    }

    // Initial state: show local projects by default
    handleLoadLocal();
}


function setupCrudPage()
{
    const form = document.querySelector('#project-crud-form');
    if (!form)
    {
        // Not on the CRUD page
        return;
    }

    const actionRadios = form.querySelectorAll('input[name="crud-action"]');
    const idField = form.querySelector('#project-id');
    const titleField = form.querySelector('#project-title');
    const descriptionField = form.querySelector('#project-description');
    const imageSmallField = form.querySelector('#project-image-small');
    const imageLargeField = form.querySelector('#project-image-large');
    const imageAltField = form.querySelector('#project-image-alt');
    const linkField = form.querySelector('#project-link');
    const tagsField = form.querySelector('#project-tags');
    const messageOutput = document.querySelector('#crud-message');

    if (!idField || actionRadios.length === 0)
    {
        return;
    }

    function getCurrentAction()
    {
        const checked = form.querySelector('input[name="crud-action"]:checked');
        return checked ? checked.value : 'create';
    }

    function showMessage(text)
    {
        if (!messageOutput)
        {
            return;
        }
        messageOutput.textContent = text;
    }

    function parseTags(text)
    {
        if (!text)
        {
            return [];
        }

        return text
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean);
    }

    // Keep visual state in sync with the selected action
    function updateCrudMode()
    {
        const action = getCurrentAction();

        if (action === 'delete')
        {
            form.classList.add('delete-mode');
            // Optional helper text, similar to contact form messaging
            showMessage('Delete a project.');
        }
        else
        {
            form.classList.remove('delete-mode');

            // Only clear the helper if we're not overwriting a more specific message
            if (messageOutput && messageOutput.textContent.startsWith('Delete'))
            {
                messageOutput.textContent = '';
            }
        }
    }

    // Respond when the user switches between Create / Update / Delete
    actionRadios.forEach((radio) =>
    {
        radio.addEventListener('change', updateCrudMode);
    });

    // Initialize once on page load
    updateCrudMode();


    form.addEventListener('submit', (event) =>
    {
        event.preventDefault();

        const action = getCurrentAction();
        const id = idField.value.trim();

        if (!id)
        {
            showMessage('Please enter a project id.');
            return;
        }

        const projects = getLocalProjects();

        if (action === 'create')
        {
            const exists = projects.some((project) => project.id === id);
            if (exists)
            {
                showMessage('A project with that id already exists. Try a different id or use update.');
                return;
            }

            const newProject = {
                id: id,
                title: titleField.value.trim() || 'Untitled project',
                description: descriptionField.value.trim(),
                imageSmall: imageSmallField.value.trim(),
                imageLarge: imageLargeField.value.trim(),
                imageAlt: imageAltField.value.trim(),
                link: linkField.value.trim(),
                tags: parseTags(tagsField.value)
            };

            projects.push(newProject);
            saveLocalProjects(projects);

            form.reset();
            // default action back to create
            const createRadio = form.querySelector('input[name="crud-action"][value="create"]');
            if (createRadio)
            {
                createRadio.checked = true;
            }

            showMessage('Project created. Visit the Play page and click "Load Local" to see it.');
            return;
        }

        if (action === 'update')
        {
            const project = projects.find((p) => p.id === id);
            if (!project)
            {
                showMessage('No project found with that id to update.');
                return;
            }

            const newTitle = titleField.value.trim();
            const newDescription = descriptionField.value.trim();
            const newImageSmall = imageSmallField.value.trim();
            const newImageLarge = imageLargeField.value.trim();
            const newImageAlt = imageAltField.value.trim();
            const newLink = linkField.value.trim();
            const newTagsText = tagsField.value.trim();

            if (newTitle)
            {
                project.title = newTitle;
            }
            if (newDescription)
            {
                project.description = newDescription;
            }
            if (newImageSmall)
            {
                project.imageSmall = newImageSmall;
            }
            if (newImageLarge)
            {
                project.imageLarge = newImageLarge;
            }
            if (newImageAlt)
            {
                project.imageAlt = newImageAlt;
            }
            if (newLink)
            {
                project.link = newLink;
            }
            if (newTagsText)
            {
                project.tags = parseTags(newTagsText);
            }

            saveLocalProjects(projects);

            showMessage('Project updated. Visit the Play page and click "Load Local" to see the changes.');
            return;
        }

        if (action === 'delete')
        {
            const index = projects.findIndex((p) => p.id === id);
            if (index === -1)
            {
                showMessage('No project found with that id to delete.');
                return;
            }

            projects.splice(index, 1);
            saveLocalProjects(projects);

            form.reset();

            const createRadio = form.querySelector('input[name="crud-action"][value="create"]');
            if (createRadio)
            {
                createRadio.checked = true;
            }

            showMessage('Project deleted. Visit the Play page and click "Load Local" to confirm.');
        }
    });
}


function setupViewTransions() {
    // No API? Don't try to transition stuff
    if (!document.startViewTransition) return;

    // Helper to update active nav link based on the URL path
    function updateActiveNav(pathname) {
        const page = pathname.split('/').pop() || 'dorjepradhan.html';

        document.querySelectorAll('nav a').forEach((link) => {
            const href = link.getAttribute('href');
            if (!href) return;
            const hrefPage = href.split('/').pop();

            if (hrefPage === page) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-view-transition]');
        if (!link) return;

        const url = new URL(link.href);

        // Only handle same-origin, same-site navigation
        if (url.origin !== window.location.origin) return;

        event.preventDefault();

        document.startViewTransition(async () => {
            const response = await fetch(url.href, {
                headers: { 'X-Requested-With': 'view-transition' }
            });

            const html = await response.text();

            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');

            const newMain = newDoc.querySelector('main');
            const currentMain = document.querySelector('main');
            const newTitle = newDoc.querySelector('title');

            if (newMain && currentMain) {
                currentMain.replaceWith(newMain);
            }

            if (newTitle) {
                document.title = newTitle.textContent;
            }

            // Update URL and active nav styling
            window.history.pushState(null, '', url.pathname);
            updateActiveNav(url.pathname);

            // Re-initialize JS behaviors for the new content
            setupContactFormJS();
            setupProjectsPage();
            setupCrudPage();
        });
    });

    // Fallback for browser back/forward buttons: full reload is fine
    window.addEventListener('popstate', () => {
        window.location.reload();
    });
}