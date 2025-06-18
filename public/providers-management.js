/**
 * Healthcare Providers Management
 * Frontend JavaScript for managing healthcare providers
 */

const API_URL = "http://192.168.0.13:5173/api/admin/healthcare-providers";

// Helper function to get auth token from localStorage
function getAuthToken() {
    return (
        localStorage.getItem("authToken") ||
        sessionStorage.getItem("authToken") ||
        ""
    );
}

// Common headers with auth token
function getHeaders() {
    return {
        "Content-Type": "application/json",
        Authorization: `Bearer ${getAuthToken()}`,
    };
}

// Show message function
function showMessage(message, type = "success") {
    const container = document.getElementById("messageContainer");
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${
                type === "success"
                    ? "check-circle"
                    : type === "danger"
                    ? "exclamation-circle"
                    : "info-circle"
            } me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    // Auto remove after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector(".alert");
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// API Functions
async function getAllProviders() {
    try {
        const response = await fetch(API_URL, {
            method: "GET",
            headers: getHeaders(),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.status === "success") {
            return result.data;
        } else {
            throw new Error(result.message || "Failed to fetch providers");
        }
    } catch (error) {
        console.error("Error fetching providers:", error);
        throw error;
    }
}

async function addProvider(providerData) {
    try {
        const response = await fetch(API_URL, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify(providerData),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.status === "success") {
            return result.data;
        } else {
            throw new Error(result.message || "Failed to add provider");
        }
    } catch (error) {
        console.error("Error adding provider:", error);
        throw error;
    }
}

async function updateProvider(id, providerData) {
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: "PUT",
            headers: getHeaders(),
            body: JSON.stringify(providerData),
        });

        if (!response.ok) {
            const text = await response.text();
            console.error("Update provider failed, response not OK:", text);
            throw new Error(`Server error: ${response.status}`);
        }

        const result = await response.json();
        if (result.status === "success") {
            return result.data;
        } else {
            throw new Error(result.message || "Failed to update provider");
        }
    } catch (error) {
        console.error("Error updating provider:", error);
        throw error;
    }
}

async function deleteProvider(id) {
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: "DELETE",
            headers: getHeaders(),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.status === "success") {
            return true;
        } else {
            throw new Error(result.message || "Failed to delete provider");
        }
    } catch (error) {
        console.error("Error deleting provider:", error);
        throw error;
    }
}

async function resetProviderPassword(id) {
    try {
        const response = await fetch(`${API_URL}/${id}/reset-password`, {
            method: "POST",
            headers: getHeaders(),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.status === "success") {
            return result.new_password;
        } else {
            throw new Error(result.message || "Failed to reset password");
        }
    } catch (error) {
        console.error("Error resetting password:", error);
        throw error;
    }
}

// DOM Elements
const userTableBody = document.getElementById("userTableBody");
const searchInput = document.getElementById("searchInput");
const userForm = document.getElementById("userForm");
const userModalLabel = document.getElementById("userModalLabel");
const userModalSubmitBtn = document.getElementById("userModalSubmitBtn");
const editIndexInput = document.getElementById("editIndex");
const exportBtn = document.getElementById("exportBtn");

// Data storage
let users = [];
let deleteIndex = null;

// Update statistics
function updateStats() {
    const doctors = users.filter(
        (u) => u.role.toLowerCase() === "doctor"
    ).length;
    const nurses = users.filter((u) => u.role.toLowerCase() === "nurse").length;
    const total = users.length;

    document.getElementById("doctorCount").textContent = doctors;
    document.getElementById("nurseCount").textContent = nurses;
    document.getElementById("totalCount").textContent = total;
}

// Render users table
function renderUsers(usersToRender = users) {
    userTableBody.innerHTML = "";

    if (usersToRender.length === 0) {
        userTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">
                    <div class="empty-state">
                        <i class="fas fa-user-md"></i>
                        <p>No healthcare providers found</p>
                    </div>
                </td>
            </tr>
        `;
        updateStats();
        return;
    }

    usersToRender.forEach((user, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    ${
                        user.picture
                            ? `<img src="${user.picture}" alt="${user.name}" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">`
                            : `<div class="rounded-circle me-3 bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px; font-weight: bold;">${user.name.charAt(
                                  0
                              )}</div>`
                    }
                    <span class="fw-medium">${user.name}</span>
                </div>
            </td>
            <td><code class="text-primary">${user.license}</code></td>
            <td><span class="role-badge ${user.role.toLowerCase()}">${
            user.role
        }</span></td>
            <td>${user.facility}</td>
            <td>${user.contact}</td>
            <td><span class="gender-badge">${user.gender}</span></td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" data-index="${index}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning reset-password-btn" data-index="${index}">
                        <i class="fas fa-key"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-index="${index}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        userTableBody.appendChild(row);
    });
    updateStats();
}

// Filter and render users
function filterAndRenderUsers() {
    const term = searchInput.value.toLowerCase();
    const filtered = users.filter(
        (user) =>
            user.name.toLowerCase().includes(term) ||
            user.license.toLowerCase().includes(term) ||
            user.role.toLowerCase().includes(term) ||
            user.facility.toLowerCase().includes(term) ||
            user.contact.toLowerCase().includes(term) ||
            user.gender.toLowerCase().includes(term)
    );
    renderUsers(filtered);
}

// Export to CSV
function exportToCSV() {
    if (users.length === 0) {
        showMessage("No data to export", "warning");
        return;
    }

    const headers = [
        "Full Name",
        "Professional License",
        "Role",
        "Healthcare Facility",
        "Contact Number",
        "Gender",
    ];

    const csvContent = [
        headers.join(","),
        ...users.map((user) =>
            [
                `"${user.name}"`,
                `"${user.license}"`,
                `"${user.role}"`,
                `"${user.facility}"`,
                `"${user.contact}"`,
                `"${user.gender}"`,
            ].join(",")
        ),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute(
        "download",
        `healthcare_providers_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.style.visibility = "hidden";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showMessage("Data exported successfully!", "success");

    const originalText = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-check me-2"></i> Exported!';
    exportBtn.disabled = true;

    setTimeout(() => {
        exportBtn.innerHTML = originalText;
        exportBtn.disabled = false;
    }, 2000);
}

// Get form data
function getUserFormData() {
    const pictureInput = document.getElementById("Picture");
    const picturePreview = document.getElementById("picturePreview");
    let pictureData = picturePreview.dataset.image || null;

    // Handle file upload
    if (pictureInput.files.length > 0 && !pictureData) {
        const file = pictureInput.files[0];
        const reader = new FileReader();
        return new Promise((resolve) => {
            reader.onload = function (e) {
                pictureData = e.target.result;
                resolve(getFormDataWithPicture(pictureData));
            };
            reader.readAsDataURL(file);
        });
    }

    return Promise.resolve(getFormDataWithPicture(pictureData));
}

function getFormDataWithPicture(pictureData) {
    return {
        Name: document.getElementById("Name").value.trim(),
        License: document.getElementById("License").value.trim(),
        userRole: document.getElementById("userRole").value,
        Facility: document.getElementById("Facility").value.trim(),
        Contact: document.getElementById("Contact").value.trim(),
        gender: document.getElementById("gender").value,
        Picture: pictureData,
    };
}

// Reset form
function resetUserForm() {
    userForm.reset();
    editIndexInput.value = "";
    userModalLabel.innerHTML =
        '<i class="fas fa-user-plus me-2"></i>Add New Provider';
    userModalSubmitBtn.innerHTML =
        '<i class="fas fa-save me-1"></i>Add Provider';
    document.getElementById("picturePreview").innerHTML = "";
    document.getElementById("picturePreview").removeAttribute("data-image");
}

// Fill form with user data
function fillUserForm(user) {
    document.getElementById("Name").value = user.name;
    document.getElementById("License").value = user.license;
    document.getElementById("userRole").value = user.role.toLowerCase();
    document.getElementById("Facility").value = user.facility;
    document.getElementById("Contact").value = user.contact;
    document.getElementById("gender").value = user.gender.toLowerCase();

    if (user.picture) {
        document.getElementById("picturePreview").innerHTML = `
            <img src="${user.picture}" alt="Current picture" class="img-thumbnail" style="max-width: 150px;">
            <p class="text-muted mt-2">Current picture</p>
        `;
        document.getElementById("picturePreview").dataset.image = user.picture;
    } else {
        document.getElementById("picturePreview").innerHTML = "";
        document.getElementById("picturePreview").removeAttribute("data-image");
    }
}

// Load and render users
async function loadAndRenderUsers() {
    try {
        showMessage("Loading healthcare providers...", "info");
        users = await getAllProviders();
        renderUsers(users);

        // Remove loading message
        const messageContainer = document.getElementById("messageContainer");
        messageContainer.innerHTML = "";

        console.log("Loaded providers:", users);
    } catch (error) {
        console.error("Error loading providers:", error);
        showMessage(
            "Error loading healthcare providers: " + error.message,
            "danger"
        );
        users = [];
        renderUsers(users);
    }
}

// Event Listeners
searchInput.addEventListener("input", filterAndRenderUsers);
exportBtn.addEventListener("click", exportToCSV);

// Picture preview
document.getElementById("Picture").addEventListener("change", function (e) {
    const file = e.target.files[0];
    const preview = document.getElementById("picturePreview");

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 150px;">
                <p class="text-muted mt-2">New picture preview</p>
            `;
            preview.dataset.image = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = "";
        preview.removeAttribute("data-image");
    }
});

// Table event handlers
userTableBody.addEventListener("click", async function (e) {
    const target = e.target.closest("button");
    if (!target) return;

    const index = parseInt(target.getAttribute("data-index"));

    if (target.classList.contains("edit-user-btn")) {
        const user = users[index];
        fillUserForm(user);
        editIndexInput.value = user.id; // Use actual ID instead of index
        userModalLabel.innerHTML =
            '<i class="fas fa-edit me-2"></i>Edit Provider';
        userModalSubmitBtn.innerHTML =
            '<i class="fas fa-save me-1"></i>Update Provider';
        new bootstrap.Modal(document.getElementById("userModal")).show();
    } else if (target.classList.contains("reset-password-btn")) {
        const user = users[index];
        try {
            const newPassword = await resetProviderPassword(user.id);
            showMessage(
                `Password reset successfully! New password: <strong>${newPassword}</strong>`,
                "success"
            );
        } catch (error) {
            showMessage("Error resetting password: " + error.message, "danger");
        }
    } else if (target.classList.contains("delete-user-btn")) {
        deleteIndex = index;
        const deleteModal = new bootstrap.Modal(
            document.getElementById("deleteConfirmModal")
        );
        deleteModal.show();
    }
});

// Delete confirmation handlers
document
    .getElementById("confirmDeleteBtn")
    .addEventListener("click", async function () {
        if (deleteIndex === null) return;

        const deleteModalEl = document.getElementById("deleteConfirmModal");
        const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);

        try {
            const user = users[deleteIndex];
            await deleteProvider(user.id);
            showMessage("Healthcare provider deleted successfully!", "success");
            await loadAndRenderUsers();
            deleteModal.hide();
        } catch (error) {
            showMessage("Error deleting provider: " + error.message, "danger");
        } finally {
            deleteIndex = null;
        }
    });

document
    .getElementById("cancelDeleteBtn")
    .addEventListener("click", function () {
        deleteIndex = null;
        showMessage("Provider deletion cancelled.", "warning");
    });

// Form submission
userForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
        const formData = await getUserFormData();
        const editId = editIndexInput.value;

        if (editId) {
            // Update existing provider
            await updateProvider(editId, formData);
            showMessage("Healthcare provider updated successfully!", "success");
        } else {
            // Add new provider
            const result = await addProvider(formData);
            showMessage(
                `Healthcare provider added successfully! Login credentials - Username: ${result.login_credentials?.username}, Password: ${result.login_credentials?.password}`,
                "success"
            );
        }

        await loadAndRenderUsers();
        resetUserForm();
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("userModal")
        );
        modal.hide();
    } catch (error) {
        console.error("Form submission error:", error);
        showMessage("Error saving provider: " + error.message, "danger");
    }
});

// Add provider button
document.getElementById("addUserBtn").addEventListener("click", function () {
    resetUserForm();
});

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    // Check authentication
    const token = getAuthToken();
    if (!token) {
        showMessage("Authentication required. Please login.", "danger");
        setTimeout(() => {
            window.location.href = "login.html";
        }, 2000);
        return;
    }

    loadAndRenderUsers();
});
