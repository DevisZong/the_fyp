// API Configuration
const API_BASE_URL = "http://localhost:8000/api"; // Adjust this to your Laravel API URL
const API_TOKEN = localStorage.getItem("api_token"); // Assuming you store the auth token in localStorage

// API Helper functions
const apiHeaders = {
    "Content-Type": "application/json",
    Accept: "application/json",
    Authorization: API_TOKEN ? `Bearer ${API_TOKEN}` : "",
};

// Dashboard API calls
const dashboardAPI = {
    // Get dashboard statistics
    async getStats() {
        try {
            const response = await fetch(`${API_BASE_URL}/dashboard/stats`, {
                method: "GET",
                headers: apiHeaders,
            });
            const data = await response.json();
            return data.success ? data.data : null;
        } catch (error) {
            console.error("Error fetching dashboard stats:", error);
            return null;
        }
    },

    // Get children data
    async getChildren(searchTerm = "") {
        try {
            const url = searchTerm
                ? `${API_BASE_URL}/dashboard/children/search?q=${encodeURIComponent(
                      searchTerm
                  )}`
                : `${API_BASE_URL}/dashboard/children`;

            const response = await fetch(url, {
                method: "GET",
                headers: apiHeaders,
            });
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.error("Error fetching children:", error);
            return [];
        }
    },

    // Get recent children
    async getRecentChildren() {
        try {
            const response = await fetch(
                `${API_BASE_URL}/dashboard/children/recent`,
                {
                    method: "GET",
                    headers: apiHeaders,
                }
            );
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.error("Error fetching recent children:", error);
            return [];
        }
    },

    // Get appointments
    async getAppointments(month = null, year = null) {
        try {
            let url = `${API_BASE_URL}/dashboard/appointments`;
            if (month && year) {
                url += `?month=${month}&year=${year}`;
            }

            const response = await fetch(url, {
                method: "GET",
                headers: apiHeaders,
            });
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.error("Error fetching appointments:", error);
            return [];
        }
    },

    // Get today's appointments
    async getTodayAppointments() {
        try {
            const response = await fetch(
                `${API_BASE_URL}/dashboard/appointments/today`,
                {
                    method: "GET",
                    headers: apiHeaders,
                }
            );
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.error("Error fetching today's appointments:", error);
            return [];
        }
    },

    // Get registration trends
    async getRegistrationTrends() {
        try {
            const response = await fetch(
                `${API_BASE_URL}/dashboard/registration-trends`,
                {
                    method: "GET",
                    headers: apiHeaders,
                }
            );
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.error("Error fetching registration trends:", error);
            return [];
        }
    },

    // Register new child
    async registerChild(childData) {
        try {
            const response = await fetch(
                `${API_BASE_URL}/healthcare/children`,
                {
                    method: "POST",
                    headers: apiHeaders,
                    body: JSON.stringify(childData),
                }
            );
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Error registering child:", error);
            return { success: false, message: "Failed to register child" };
        }
    },
};

// Global variables for data storage
let allChildren = [];
let appointments = [];
let dashboardStats = {};

// Initialize dashboard with real data
document.addEventListener("DOMContentLoaded", async function () {
    // Check if user is authenticated
    if (!API_TOKEN) {
        console.warn("No API token found. Please login first.");
        // Redirect to login or show login form
        return;
    }

    await loadDashboardData();
    setupEventListeners();
});

// Load all dashboard data
async function loadDashboardData() {
    try {
        // Show loading state
        showLoadingState();

        // Load data in parallel
        const [stats, recentChildrenData, appointmentsData, trendsData] =
            await Promise.all([
                dashboardAPI.getStats(),
                dashboardAPI.getRecentChildren(),
                dashboardAPI.getTodayAppointments(),
                dashboardAPI.getRegistrationTrends(),
            ]);

        // Update global variables
        dashboardStats = stats || {};
        allChildren = recentChildrenData || [];
        appointments = appointmentsData || [];

        // Update UI
        updateStats(stats);
        renderRecentChildren(recentChildrenData);
        renderAppointments(appointmentsData);
        createRegistrationChart(trendsData);
        renderCalendar();

        // Hide loading state
        hideLoadingState();
    } catch (error) {
        console.error("Error loading dashboard data:", error);
        showErrorState("Failed to load dashboard data");
    }
}

// Update dashboard statistics
function updateStats(stats) {
    if (stats) {
        document.getElementById("totalChildren").textContent =
            stats.totalChildren || 0;
        document.getElementById("todayAppointments").textContent =
            stats.todayAppointments || 0;
        document.getElementById("thisMonthVaccinations").textContent =
            stats.thisMonthVaccinations || 0;
        document.getElementById("pendingFollowups").textContent =
            stats.pendingFollowups || 0;
    }
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(handleSearch, 300));
    }

    // Register form
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", handleChildRegistration);
    }
}

// Enhanced search functionality
async function handleSearch() {
    const searchTerm = document
        .getElementById("searchInput")
        .value.toLowerCase()
        .trim();
    const recentSection = document.getElementById("recentChildren");
    const allTableSection = document.getElementById("allChildrenTable");
    const searchSection = document.getElementById("searchResults");

    if (searchTerm === "") {
        // Show recent children, hide search results
        recentSection.style.display = "block";
        searchSection.style.display = "none";
        if (allTableSection.style.display === "block") {
            allTableSection.style.display = "block";
        }
        return;
    }

    // Hide recent children and table, show search results
    recentSection.style.display = "none";
    allTableSection.style.display = "none";
    searchSection.style.display = "block";

    // Fetch search results from API
    const searchResults = await dashboardAPI.getChildren(searchTerm);
    renderSearchResults(searchResults);
}

// Enhanced child registration
async function handleChildRegistration(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const childData = {
        childName: formData.get("childName"),
        date_of_birth: formData.get("childAge"), // You might need to adjust this
        fatherName: formData.get("guardianName"),
        phoneNo: formData.get("guardianPhone"),
        // Add other required fields based on your Child model
    };

    try {
        const result = await dashboardAPI.registerChild(childData);

        if (result.success) {
            // Reload dashboard data
            await loadDashboardData();
            closeRegisterModal();
            showSuccessMessage("Child registered successfully!");
        } else {
            showErrorMessage(result.message || "Failed to register child");
        }
    } catch (error) {
        console.error("Registration error:", error);
        showErrorMessage("Failed to register child");
    }
}

// Enhanced chart creation with real data
function createRegistrationChart(trendsData) {
    const ctx = document.getElementById("registrationChart").getContext("2d");

    const labels = trendsData?.map((item) => item.month) || [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
    ];
    const data = trendsData?.map((item) => item.count) || [
        12, 18, 15, 24, 20, 28,
    ];

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "New Children Registered",
                    data: data,
                    backgroundColor: [
                        "rgba(102, 126, 234, 0.8)",
                        "rgba(118, 75, 162, 0.8)",
                        "rgba(17, 153, 142, 0.8)",
                        "rgba(56, 239, 125, 0.8)",
                        "rgba(255, 193, 7, 0.8)",
                        "rgba(220, 53, 69, 0.8)",
                    ],
                    borderColor: [
                        "#667eea",
                        "#764ba2",
                        "#11998e",
                        "#38ef7d",
                        "#ffc107",
                        "#dc3545",
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "top",
                },
                title: {
                    display: true,
                    text: "Monthly Children Registration Trends",
                    font: {
                        size: 16,
                        weight: "bold",
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: "Number of Children Registered",
                    },
                    ticks: {
                        stepSize: 5,
                    },
                },
                x: {
                    title: {
                        display: true,
                        text: "Month",
                    },
                },
            },
            animation: {
                duration: 2000,
                easing: "easeInOutQuart",
            },
        },
    });
}

// Enhanced appointments rendering
function renderAppointments(appointmentsData) {
    const appointmentsList = document.getElementById("appointmentsList");
    appointmentsList.innerHTML = "";

    if (!appointmentsData || appointmentsData.length === 0) {
        appointmentsList.innerHTML =
            '<p style="text-align: center; color: #64748b; padding: 20px;">No appointments today</p>';
        return;
    }

    appointmentsData.forEach((appointment) => {
        const appointmentElement = document.createElement("div");
        appointmentElement.className = "appointment-item";
        appointmentElement.innerHTML = `
            <div class="appointment-info">
                <h4>${appointment.child}</h4>
                <p>${appointment.type}</p>
            </div>
            <div class="appointment-time">${appointment.time}</div>
        `;
        appointmentsList.appendChild(appointmentElement);
    });
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showLoadingState() {
    // Add loading indicators to your dashboard
    console.log("Loading dashboard data...");
}

function hideLoadingState() {
    // Remove loading indicators
    console.log("Dashboard data loaded");
}

function showErrorState(message) {
    // Show error message to user
    console.error(message);
    alert(message); // Replace with better error handling
}

function showSuccessMessage(message) {
    // Show success message to user
    console.log(message);
    alert(message); // Replace with better success handling
}

function showErrorMessage(message) {
    // Show error message to user
    console.error(message);
    alert(message); // Replace with better error handling
}

// Keep existing functions that don't need API integration
function renderRecentChildren(childrenData) {
    const recentGrid = document.getElementById("recentGrid");
    recentGrid.innerHTML = "";

    if (!childrenData || childrenData.length === 0) {
        recentGrid.innerHTML =
            '<p style="text-align: center; color: #64748b; padding: 20px; grid-column: 1 / -1;">No children registered yet.</p>';
        return;
    }

    childrenData.forEach((child) => {
        const childCard = document.createElement("div");
        childCard.className = "child-card";
        childCard.innerHTML = `
            <div class="child-card-header">
                <h3 class="child-name">${child.name}</h3>
                <span class="child-age">${child.age} years</span>
            </div>
            <div class="child-info">
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <span>Guardian: ${child.guardian}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                    </svg>
                    <span>${child.phone}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2.5-9H18V1h-2v1H8V1H6v1H4.5C3.12 2 2 3.12 2 4.5v15C2 20.88 3.12 22 4.5 22h15c1.38 0 2.5-1.12 2.5-2.5v-15C22 3.12 20.88 2 19.5 2z"/>
                    </svg>
                    <span>Last Visit: ${child.lastVisit}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <span>Next: ${child.nextVaccination}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                    </svg>
                    <span>Registered: ${child.registrationDate}</span>
                </div>
            </div>
        `;
        recentGrid.appendChild(childCard);
    });
}

function renderSearchResults(filteredChildren) {
    const searchGrid = document.getElementById("searchResultsGrid");
    searchGrid.innerHTML = "";

    if (filteredChildren.length === 0) {
        searchGrid.innerHTML =
            '<p style="text-align: center; color: #64748b; padding: 20px; grid-column: 1 / -1;">No children found matching your search.</p>';
        return;
    }

    filteredChildren.forEach((child) => {
        const childCard = document.createElement("div");
        childCard.className = "child-card";
        childCard.innerHTML = `
            <div class="child-card-header">
                <h3 class="child-name">${child.name}</h3>
                <span class="child-age">${child.age} years</span>
            </div>
            <div class="child-info">
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <span>Guardian: ${child.guardian}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                    </svg>
                    <span>${child.phone}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2.5-9H18V1h-2v1H8V1H6v1H4.5C3.12 2 2 3.12 2 4.5v15C2 20.88 3.12 22 4.5 22h15c1.38 0 2.5-1.12 2.5-2.5v-15C22 3.12 20.88 2 19.5 2z"/>
                    </svg>
                    <span>Last Visit: ${child.lastVisit}</span>
                </div>
                <div class="info-row">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <span>Next: ${child.nextVaccination}</span>
                </div>
            </div>
        `;
        searchGrid.appendChild(childCard);
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

        // Check for appointments on this day
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(
            2,
            "0"
        )}-${String(day).padStart(2, "0")}`;
        if (appointments && appointments.some((apt) => apt.date === dateStr)) {
            dayElement.classList.add("has-appointment");
        }

        calendar.appendChild(dayElement);
    }
}

function renderChildrenTable() {
    const tbody = document.getElementById("childrenTableBody");
    tbody.innerHTML = "";

    allChildren.forEach((child) => {
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

        // Load all children data if not already loaded
        loadAllChildrenForTable();
    } else {
        // Show recent children
        allTableSection.style.display = "none";
        recentSection.style.display = "block";
        toggleText.textContent = "Show All";
    }
}

async function loadAllChildrenForTable() {
    try {
        const children = await dashboardAPI.getChildren();
        allChildren = children;
        renderChildrenTable();
    } catch (error) {
        console.error("Error loading all children:", error);
        showErrorMessage("Failed to load children data");
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
