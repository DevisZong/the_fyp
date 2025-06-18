const BASE_URL = "http://127.0.0.1:8000/api";

/**
 * Fetch child users from the backend API.
 * @returns {Promise<Array>} Array of child user objects.
 */
export async function fetchUsers() {
    try {
        const token = localStorage.getItem("authToken") || "";
        const response = await fetch(`${BASE_URL}/admin/users`, {
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
        });

        if (!response.ok) {
            if (response.status === 401) {
                throw new Error("Authentication failed. Please login again.");
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const json = await response.json();
        if (json.status === "success" && Array.isArray(json.data)) {
            return json; // Return full response to access total
        } else {
            console.error("Unexpected API response format:", json);
            return { data: [], total: 0 };
        }
    } catch (error) {
        console.error("Failed to fetch users:", error);
        return { data: [], total: 0 };
    }
}

/**
 * Reset password for a child using child number.
 * @param {string} childNo - The child number.
 * @returns {Promise<Object>} Response JSON from the backend.
 */
export async function resetUserPassword(childNo) {
    try {
        const token = localStorage.getItem("authToken") || "";
        const response = await fetch(`${BASE_URL}/admin/reset-password`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
            },
            body: JSON.stringify({
                identifier_used: "child_no",
                identifier_value: childNo,
            }),
        });

        if (!response.ok) {
            if (response.status === 401) {
                throw new Error("Authentication failed. Please login again.");
            }
            const errorData = await response.json();
            throw new Error(
                errorData.message || `HTTP error! status: ${response.status}`
            );
        }

        const json = await response.json();
        return json;
    } catch (error) {
        console.error("Failed to reset user password:", error);
        return { status: "error", message: error.message };
    }
}

/**
 * Search child users by name, child number, or parent names.
 * @param {string} searchTerm - The search term.
 * @returns {Promise<Object>} Response JSON from the backend.
 */
export async function searchUsers(searchTerm) {
    try {
        const token = localStorage.getItem("authToken") || "";
        const response = await fetch(`${BASE_URL}/admin/search-users`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
            },
            body: JSON.stringify({
                search: searchTerm,
                limit: 50,
            }),
        });

        if (!response.ok) {
            if (response.status === 401) {
                throw new Error("Authentication failed. Please login again.");
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const json = await response.json();
        return json;
    } catch (error) {
        console.error("Failed to search users:", error);
        return { status: "error", message: error.message, data: [] };
    }
}
