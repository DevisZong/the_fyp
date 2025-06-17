// Complete dashboard.js with all functionality

// Keep original functions but add new ones for API integration
function toggleView() {
    const recentSection = document.getElementById("recentChildren");
    const allTableSection = document.getElementById("allChildrenTable");
    const toggleText = document.getElementById("viewToggleText");

    if (
        allTableSection.style.display === "none" ||
        allTableSection.style.display === ""
    ) {
        // Show all children in table format
        allTableSection.style.display = "block";
        recentSection.style.display = "none";
        toggleText.textContent = "Show Recent";

        // Load all children data
        loadAllChildren();
    } else {
        // Show recent children
        allTableSection.style.display = "none";
        recentSection.style.display = "block";
        toggleText.textContent = "Show All";
    }
}

async function loadAllChildren() {
    const children = await dashboardAPI.getChildren();
    renderChildrenTable(children);
}

function renderChildrenTable(children) {
    const tbody = document.getElementById("childrenTableBody");
    tbody.innerHTML = "";

    if (!children || children.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No children registered yet.</td></tr>';
        return;
    }

    children.forEach((child) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${child.name}</td>
            <td>${child.age} years</td>
            <td>${child.guardian}</td>
            <td>${child.phone}</td>
            <td>${child.lastVisit}</td>
            <td>${child.nextVaccination}</td>
        `;
        tbody.appendChild(row);
    });
}

function renderCalendar() {
    const calendar = document.getElementById("calendar");
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();

    // Clear calendar
    calendar.innerHTML = "";

    // Add day headers
    const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    days.forEach((day) => {
        const dayHeader = document.createElement("div");
        dayHeader.className = "calendar-header";
        dayHeader.textContent = day;
        calendar.appendChild(dayHeader);
    });

    // Get first day of month and number of days
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement("div");
        emptyDay.className = "calendar-day";
        calendar.appendChild(emptyDay);
    }

    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement("div");
        dayElement.className = "calendar-day";
        dayElement.textContent = day;

        // Highlight today
        if (day === today.getDate()) {
            dayElement.classList.add("today");
        }

        // You can add appointment checking here if needed
        // const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        // if (appointments.some(apt => apt.date === dateStr)) {
        //     dayElement.classList.add('has-appointment');
        // }

        calendar.appendChild(dayElement);
    }
}

// Modal functions
function openRegisterModal() {
    document.getElementById("registerModal").style.display = "block";
}

function closeRegisterModal() {
    document.getElementById("registerModal").style.display = "none";
    document.getElementById("registerForm").reset();
}

// Print functionality
function printRecords() {
    window.print();
}

// Refresh dashboard
async function refreshDashboard() {
    await loadDashboardData();
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById("registerModal");
    if (event.target === modal) {
        closeRegisterModal();
    }
};

// Enhanced loading states
function showLoadingState() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
        overlay.style.display = "flex";
    }
}

function hideLoadingState() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
        overlay.style.display = "none";
    }
}

// Enhanced error and success handling
function showErrorState(message) {
    hideLoadingState();

    // Create or update error message
    let errorDiv = document.getElementById("errorMessage");
    if (!errorDiv) {
        errorDiv = document.createElement("div");
        errorDiv.id = "errorMessage";
        errorDiv.className = "error-message";
        document
            .querySelector(".dashboard")
            .insertBefore(
                errorDiv,
                document.querySelector(".header").nextSibling
            );
    }

    errorDiv.textContent = message;
    errorDiv.style.display = "block";

    // Auto hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.display = "none";
    }, 5000);
}

function showSuccessMessage(message) {
    // Create or update success message
    let successDiv = document.getElementById("successMessage");
    if (!successDiv) {
        successDiv = document.createElement("div");
        successDiv.id = "successMessage";
        successDiv.className = "success-message";
        document
            .querySelector(".dashboard")
            .insertBefore(
                successDiv,
                document.querySelector(".header").nextSibling
            );
    }

    successDiv.textContent = message;
    successDiv.style.display = "block";

    // Auto hide after 3 seconds
    setTimeout(() => {
        successDiv.style.display = "none";
    }, 3000);
}

function showErrorMessage(message) {
    showErrorState(message);
}

// Additional utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatTime(timeString) {
    const time = new Date(`2000-01-01 ${timeString}`);
    return time.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
}

// Check authentication status
function checkAuthStatus() {
    const token = localStorage.getItem("api_token");
    if (!token) {
        // Redirect to login or show login form
        console.warn("No authentication token found");
        return false;
    }
    return true;
}

// Initialize authentication check
if (!checkAuthStatus()) {
    // Handle unauthenticated state
    document.body.innerHTML = `
        <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
            <h2>Authentication Required</h2>
            <p>Please login to access the dashboard.</p>
            <button onclick="window.location.href='/login'" class="btn btn-primary">Go to Login</button>
        </div>
    `;
}
function toggleView() {
    const recentSection = document.getElementById("recentChildren");
    const allTableSection = document.getElementById("allChildrenTable");
    const toggleText = document.getElementById("viewToggleText");

    if (
        allTableSection.style.display === "none" ||
        allTableSection.style.display === ""
    ) {
        // Show all children in table format
        allTableSection.style.display = "block";
        recentSection.style.display = "none";
        toggleText.textContent = "Show Recent";

        // Load all children data
        loadAllChildren();
    } else {
        // Show recent children
        allTableSection.style.display = "none";
        recentSection.style.display = "block";
        toggleText.textContent = "Show All";
    }
}

async function loadAllChildren() {
    const children = await dashboardAPI.getChildren();
    renderChildrenTable(children);
}

function renderChildrenTable(children) {
    const tbody = document.getElementById("childrenTableBody");
    tbody.innerHTML = "";

    if (!children || children.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No children registered yet.</td></tr>';
        return;
    }

    children.forEach((child) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${child.name}</td>
            <td>${child.age} years</td>
            <td>${child.guardian}</td>
            <td>${child.phone}</td>
            <td>${child.lastVisit}</td>
            <td>${child.nextVaccination}</td>
        `;
        tbody.appendChild(row);
    });
}

function renderCalendar() {
    const calendar = document.getElementById("calendar");
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();

    // Clear calendar
    calendar.innerHTML = "";

    // Add day headers
    const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    days.forEach((day) => {
        const dayHeader = document.createElement("div");
        dayHeader.className = "calendar-header";
        dayHeader.textContent = day;
        calendar.appendChild(dayHeader);
    });

    // Get first day of month and number of days
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement("div");
        emptyDay.className = "calendar-day";
        calendar.appendChild(emptyDay);
    }

    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement("div");
        dayElement.className = "calendar-day";
        dayElement.textContent = day;

        // Highlight today
        if (day === today.getDate()) {
            dayElement.classList.add("today");
        }

        // You can add appointment checking here if needed
        // const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        // if (appointments.some(apt => apt.date === dateStr)) {
        //     dayElement.classList.add('has-appointment');
        // }

        calendar.appendChild(dayElement);
    }
}

// Modal functions
function openRegisterModal() {
    document.getElementById("registerModal").style.display = "block";
}

function closeRegisterModal() {
    document.getElementById("registerModal").style.display = "none";
    document.getElementById("registerForm").reset();
}

// Print functionality
function printRecords() {
    window.print();
}

// Refresh dashboard
async function refreshDashboard() {
    await loadDashboardData();
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById("registerModal");
    if (event.target === modal) {
        closeRegisterModal();
    }
};

// Enhanced loading states
function showLoadingState() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
        overlay.style.display = "flex";
    }
}

function hideLoadingState() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
        overlay.style.display = "none";
    }
}

// Enhanced error and success handling
function showErrorState(message) {
    hideLoadingState();

    // Create or update error message
    let errorDiv = document.getElementById("errorMessage");
    if (!errorDiv) {
        errorDiv = document.createElement("div");
        errorDiv.id = "errorMessage";
        errorDiv.className = "error-message";
        document
            .querySelector(".dashboard")
            .insertBefore(
                errorDiv,
                document.querySelector(".header").nextSibling
            );
    }

    errorDiv.textContent = message;
    errorDiv.style.display = "block";

    // Auto hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.display = "none";
    }, 5000);
}

function showSuccessMessage(message) {
    // Create or update success message
    let successDiv = document.getElementById("successMessage");
    if (!successDiv) {
        successDiv = document.createElement("div");
        successDiv.id = "successMessage";
        successDiv.className = "success-message";
        document
            .querySelector(".dashboard")
            .insertBefore(
                successDiv,
                document.querySelector(".header").nextSibling
            );
    }

    successDiv.textContent = message;
    successDiv.style.display = "block";

    // Auto hide after 3 seconds
    setTimeout(() => {
        successDiv.style.display = "none";
    }, 3000);
}

function showErrorMessage(message) {
    showErrorState(message);
}

// Additional utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatTime(timeString) {
    const time = new Date(`2000-01-01 ${timeString}`);
    return time.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
}

// Check authentication status
function checkAuthStatus() {
    const token = localStorage.getItem("api_token");
    if (!token) {
        // Redirect to login or show login form
        console.warn("No authentication token found");
        return false;
    }
    return true;
}

// Initialize authentication check
if (!checkAuthStatus()) {
    // Handle unauthenticated state
    document.body.innerHTML = `
        <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
            <h2>Authentication Required</h2>
            <p>Please login to access the dashboard.</p>
            <button onclick="window.location.href='/login'" class="btn btn-primary">Go to Login</button>
        </div>
    `;
}
