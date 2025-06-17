// Complete dashboard-enhanced.js with all functionality

// Toggle between recent and all children views
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

// Load all children for table view
async function loadAllChildren() {
    try {
        const children = await dashboardAPI.getChildren();
        renderChildrenTable(children);
    } catch (error) {
        console.error("Error loading all children:", error);
        showErrorMessage("Failed to load children data");
    }
}

// Render children in table format
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

// Render calendar with appointments
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

        // Check for appointments on this day
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(
            2,
            "0"
        )}-${String(day).padStart(2, "0")}`;
        if (
            typeof appointments !== "undefined" &&
            appointments.some &&
            appointments.some((apt) => apt.date === dateStr)
        ) {
            dayElement.classList.add("has-appointment");
        }

        // Add click event for day selection
        dayElement.addEventListener("click", () => {
            // Remove previous selection
            calendar
                .querySelectorAll(".calendar-day.selected")
                .forEach((el) => {
                    el.classList.remove("selected");
                });
            // Add selection to clicked day
            dayElement.classList.add("selected");

            // Show appointments for selected day (if implemented)
            showAppointmentsForDay(dateStr);
        });

        calendar.appendChild(dayElement);
    }
}

// Show appointments for selected day
function showAppointmentsForDay(dateStr) {
    if (typeof appointments === "undefined" || !appointments.filter) return;

    const dayAppointments = appointments.filter((apt) => apt.date === dateStr);

    if (dayAppointments.length > 0) {
        console.log(`Appointments for ${dateStr}:`, dayAppointments);
        // You can implement a popup or side panel to show day appointments here
    }
}

// Modal functions
function openRegisterModal() {
    document.getElementById("registerModal").style.display = "block";
    // Focus on first input
    setTimeout(() => {
        document.getElementById("childName").focus();
    }, 100);
}

function closeRegisterModal() {
    document.getElementById("registerModal").style.display = "none";
    document.getElementById("registerForm").reset();

    // Clear any error messages in the modal
    const errorMessages = document.querySelectorAll(
        "#registerModal .error-message"
    );
    errorMessages.forEach((msg) => msg.remove());
}

// Enhanced form validation
function validateRegisterForm(formData) {
    const errors = [];

    if (
        !formData.get("childName") ||
        formData.get("childName").trim().length < 2
    ) {
        errors.push("Child name must be at least 2 characters long");
    }

    if (!formData.get("dateOfBirth")) {
        errors.push("Date of birth is required");
    } else {
        const birthDate = new Date(formData.get("dateOfBirth"));
        const today = new Date();
        if (birthDate > today) {
            errors.push("Date of birth cannot be in the future");
        }
        if (birthDate < new Date("1900-01-01")) {
            errors.push("Please enter a valid date of birth");
        }
    }

    if (!formData.get("gender")) {
        errors.push("Gender is required");
    }

    if (
        !formData.get("fatherName") ||
        formData.get("fatherName").trim().length < 2
    ) {
        errors.push("Father name must be at least 2 characters long");
    }

    if (
        !formData.get("motherName") ||
        formData.get("motherName").trim().length < 2
    ) {
        errors.push("Mother name must be at least 2 characters long");
    }

    const phoneNo = formData.get("phoneNo");
    if (!phoneNo || phoneNo.length < 9) {
        errors.push("Valid phone number is required");
    }

    const birthWeight = parseFloat(formData.get("birthWeight"));
    if (!birthWeight || birthWeight < 0.5 || birthWeight > 10) {
        errors.push("Birth weight should be between 0.5kg and 10kg");
    }

    const birthHeight = parseFloat(formData.get("birthHeight"));
    if (!birthHeight || birthHeight < 30 || birthHeight > 70) {
        errors.push("Birth height should be between 30cm and 70cm");
    }

    return errors;
}

// Print functionality with options
function printRecords() {
    const printWindow = window.open("", "_blank");
    const printContent = document.querySelector(".print-section").innerHTML;

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Healthcare Records</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .card-title { font-size: 24px; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .child-card { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
                .child-name { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
                .info-row { margin: 5px 0; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>Healthcare Provider Dashboard - Records</h1>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
            </div>
            ${printContent}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Refresh dashboard with loading indicator
async function refreshDashboard() {
    try {
        showLoadingState();
        await loadDashboardData();
        showSuccessMessage("Dashboard refreshed successfully");
    } catch (error) {
        console.error("Error refreshing dashboard:", error);
        showErrorMessage("Failed to refresh dashboard");
    }
}

// Enhanced loading states
function showLoadingState() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
        overlay.style.display = "flex";
    }

    // Add loading class to main elements
    const elements = document.querySelectorAll(
        ".stat-number, .recent-grid, .appointments-list"
    );
    elements.forEach((el) => {
        el.classList.add("loading-state");
    });
}

function hideLoadingState() {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
        overlay.style.display = "none";
    }

    // Remove loading class from main elements
    const elements = document.querySelectorAll(".loading-state");
    elements.forEach((el) => {
        el.classList.remove("loading-state");
    });
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
        errorDiv.style.cssText = `
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
        `;
        document
            .querySelector(".dashboard")
            .insertBefore(
                errorDiv,
                document.querySelector(".header").nextSibling
            );
    }

    errorDiv.innerHTML = `
        <strong>Error:</strong> ${message}
        <button onclick="this.parentElement.style.display='none'" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
    `;
    errorDiv.style.display = "block";

    // Auto hide after 8 seconds
    setTimeout(() => {
        if (errorDiv.style.display !== "none") {
            errorDiv.style.display = "none";
        }
    }, 8000);
}

function showSuccessMessage(message) {
    // Create or update success message
    let successDiv = document.getElementById("successMessage");
    if (!successDiv) {
        successDiv = document.createElement("div");
        successDiv.id = "successMessage";
        successDiv.className = "success-message";
        successDiv.style.cssText = `
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(21, 87, 36, 0.1);
        `;
        document
            .querySelector(".dashboard")
            .insertBefore(
                successDiv,
                document.querySelector(".header").nextSibling
            );
    }

    successDiv.innerHTML = `
        <strong>Success:</strong> ${message}
        <button onclick="this.parentElement.style.display='none'" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
    `;
    successDiv.style.display = "block";

    // Auto hide after 5 seconds
    setTimeout(() => {
        if (successDiv.style.display !== "none") {
            successDiv.style.display = "none";
        }
    }, 5000);
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
    if (!timeString) return "N/A";

    try {
        const time = new Date(`2000-01-01 ${timeString}`);
        return time.toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
            hour12: true,
        });
    } catch (error) {
        return timeString; // Return original if parsing fails
    }
}

function formatPhoneNumber(phone) {
    if (!phone) return "N/A";

    // Basic phone number formatting
    const phoneStr = phone.toString().replace(/\D/g, "");
    if (phoneStr.length === 10) {
        return `(${phoneStr.slice(0, 3)}) ${phoneStr.slice(
            3,
            6
        )}-${phoneStr.slice(6)}`;
    } else if (phoneStr.length === 12 && phoneStr.startsWith("255")) {
        return `+${phoneStr.slice(0, 3)} ${phoneStr.slice(
            3,
            6
        )} ${phoneStr.slice(6, 9)} ${phoneStr.slice(9)}`;
    }
    return phone;
}

// Check authentication status
function checkAuthStatus() {
    const token = localStorage.getItem("api_token");
    if (!token) {
        console.warn("No authentication token found");
        return false;
    }
    return true;
}

// Export data functionality
function exportData(format = "csv") {
    if (!allChildren || allChildren.length === 0) {
        showErrorMessage("No data available to export");
        return;
    }

    if (format === "csv") {
        exportToCSV();
    } else if (format === "json") {
        exportToJSON();
    }
}

function exportToCSV() {
    const headers = [
        "Name",
        "Age",
        "Guardian",
        "Phone",
        "Last Visit",
        "Next Vaccination",
        "Registration Date",
    ];
    const csvContent = [
        headers.join(","),
        ...allChildren.map((child) =>
            [
                `"${child.name}"`,
                child.age,
                `"${child.guardian}"`,
                child.phone,
                child.lastVisit,
                `"${child.nextVaccination}"`,
                child.registrationDate,
            ].join(",")
        ),
    ].join("\n");

    downloadFile(csvContent, "children_records.csv", "text/csv");
}

function exportToJSON() {
    const jsonContent = JSON.stringify(allChildren, null, 2);
    downloadFile(jsonContent, "children_records.json", "application/json");
}

function downloadFile(content, filename, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    showSuccessMessage(`Data exported as ${filename}`);
}

// Keyboard shortcuts
document.addEventListener("keydown", function (e) {
    // Ctrl+R or F5 - Refresh dashboard
    if ((e.ctrlKey && e.key === "r") || e.key === "F5") {
        e.preventDefault();
        refreshDashboard();
    }

    // Ctrl+N - New registration
    if (e.ctrlKey && e.key === "n") {
        e.preventDefault();
        openRegisterModal();
    }

    // Escape - Close modal
    if (e.key === "Escape") {
        closeRegisterModal();
    }

    // Ctrl+P - Print
    if (e.ctrlKey && e.key === "p") {
        e.preventDefault();
        printRecords();
    }
});

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById("registerModal");
    if (event.target === modal) {
        closeRegisterModal();
    }
};

// Search enhancements
function clearSearch() {
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.value = "";
        searchInput.dispatchEvent(new Event("input"));
    }
}

// Auto-save search preferences
function saveSearchPreference(searchTerm) {
    if (searchTerm) {
        localStorage.setItem("dashboard_last_search", searchTerm);
    }
}

function loadSearchPreference() {
    const lastSearch = localStorage.getItem("dashboard_last_search");
    const searchInput = document.getElementById("searchInput");
    if (lastSearch && searchInput) {
        searchInput.value = lastSearch;
    }
}

// Theme toggle (if you want to add dark mode)
function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute("data-theme");
    const newTheme = currentTheme === "dark" ? "light" : "dark";

    body.setAttribute("data-theme", newTheme);
    localStorage.setItem("dashboard_theme", newTheme);
}

function loadThemePreference() {
    const savedTheme = localStorage.getItem("dashboard_theme");
    if (savedTheme) {
        document.body.setAttribute("data-theme", savedTheme);
    }
}

// Initialize preferences on load
document.addEventListener("DOMContentLoaded", function () {
    loadThemePreference();
    loadSearchPreference();
});

// Handle authentication errors
function handleAuthError() {
    localStorage.removeItem("api_token");
    showErrorState("Session expired. Please login again.");

    setTimeout(() => {
        window.location.href = "/login";
    }, 2000);
}

// Network status handling
function handleNetworkError() {
    showErrorState(
        "Network connection error. Please check your internet connection."
    );
}

// Initialize network status monitoring
window.addEventListener("online", function () {
    showSuccessMessage("Connection restored");
    refreshDashboard();
});

window.addEventListener("offline", function () {
    showErrorMessage("You are offline. Some features may not work.");
});

// Performance monitoring
function measurePerformance(label, fn) {
    const start = performance.now();
    const result = fn();
    const end = performance.now();
    console.log(`${label} took ${end - start} milliseconds`);
    return result;
}

// Initialize authentication check
if (!checkAuthStatus()) {
    // Handle unauthenticated state
    document.body.innerHTML = `
        <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
            <div style="text-align: center; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <h2 style="color: #2c3e50; margin-bottom: 15px;">🔐 Authentication Required</h2>
                <p style="color: #64748b; margin-bottom: 25px;">Please login to access the healthcare dashboard.</p>
                <button onclick="window.location.href='/login'" style="
                    background: linear-gradient(135deg, #489ae7, #489ae7);
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    transition: all 0.3s ease;
                ">Go to Login</button>
            </div>
        </div>
    `;
}
