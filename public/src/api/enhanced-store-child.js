// Use localStorage instead of import for tokens
const authToken = localStorage.getItem("authToken");
const healthcareProviderId = localStorage.getItem("healthcareProviderId");

const formatPhoneNumber = (phone) => {
    // Remove any non-digit characters
    let cleanPhone = phone.replace(/\D/g, "");

    // If the number starts with 0, remove it
    if (cleanPhone.startsWith("0")) {
        cleanPhone = cleanPhone.substring(1);
    }

    // Add 255 prefix if not present
    if (!cleanPhone.startsWith("255")) {
        cleanPhone = "255" + cleanPhone;
    }

    return cleanPhone;
};

// Function to show success message
const showSuccessMessage = (message) => {
    // Create success alert element
    const alertDiv = document.createElement("div");
    alertDiv.className =
        "alert alert-success alert-dismissible fade show position-fixed";
    alertDiv.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
    alertDiv.innerHTML = `
    <i class="fas fa-check-circle me-2"></i>
    <strong>Success!</strong> ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;

    // Add to body
    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
};

// Function to show error message
const showErrorMessage = (message) => {
    const alertDiv = document.createElement("div");
    alertDiv.className =
        "alert alert-danger alert-dismissible fade show position-fixed";
    alertDiv.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
    alertDiv.innerHTML = `
    <i class="fas fa-exclamation-circle me-2"></i>
    <strong>Error!</strong> ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
};

// Function to fetch next child number
const fetchNextChildNumber = async () => {
    try {
        const response = await fetch(
            "http://localhost:5173/api/healthcare/children/next-number",
            {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    Authorization: `Bearer ${authToken}`,
                },
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status} Error`);
        }

        const responseData = await response.json();

        if (responseData.status === "success") {
            return responseData.data.nextChildNumber;
        } else {
            throw new Error(
                responseData.message || "Failed to get next child number"
            );
        }
    } catch (error) {
        console.error("Error fetching next child number:", error);
        throw error;
    }
};

// Function to auto-fill child number
const autoFillChildNumber = async () => {
    const childNumberInput = document.getElementById("child-number");

    if (!childNumberInput) {
        console.error("Child number input field not found");
        return;
    }

    try {
        const nextChildNumber = await fetchNextChildNumber();
        childNumberInput.value = nextChildNumber;

        console.log("Auto-filled child number:", nextChildNumber);
    } catch (error) {
        childNumberInput.value = "";
        childNumberInput.placeholder = "Error loading number - please refresh";
        showErrorMessage(
            "Could not auto-fill child number. Please refresh the page."
        );
    }
};

document.addEventListener("DOMContentLoaded", () => {
    // Set max date for birth date to today
    const birthDateInput = document.getElementById("birth-date");
    if (birthDateInput) {
        const today = new Date().toISOString().split("T")[0];
        birthDateInput.setAttribute("max", today);
    }
    // Auto-fill child number when page loads
    autoFillChildNumber();

    const registerForm = document.getElementById("registerForm");
    if (!registerForm) return;

    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        // Collect parent info - matching HTML field IDs
        const parentName = document.getElementById("parent-name").value.trim(); // This is mother's name in HTML
        const fatherName = document.getElementById("father-name").value.trim();
        const motherAge = document.getElementById("mother-age").value
            ? parseInt(document.getElementById("mother-age").value, 10)
            : null;
        const parentEmail = document
            .getElementById("parent-email")
            .value.trim();
        const parentPhone = document
            .getElementById("parent-phone")
            .value.trim();
        const street = document.getElementById("street").value.trim();
        const ward = document.getElementById("ward").value.trim();
        const district = document.getElementById("district").value.trim(); // This is called 'district' in HTML

        // Collect child info - matching HTML field IDs
        const clinicName = document.getElementById("clinic-name").value.trim();
        const childNo = document.getElementById("child-number").value.trim();
        const childName = document.getElementById("child-name").value.trim();
        const gender = document.getElementById("child-gender").value;
        const date_of_birth = document.getElementById("birth-date").value;
        const birthWeight =
            parseFloat(document.getElementById("birth-weight").value) || null;
        const birthHeight =
            parseFloat(document.getElementById("birth-height").value) || null;
        const birthFacility = clinicName;
        const birthAttendant = document.getElementById("birthAttendant").value;
        const birthPlace = document.getElementById("birthPlace").value;

        // Format phone number before adding to data object
        const formattedPhone = formatPhoneNumber(parentPhone);

        // Prepare data object
        const data = {
            childNo,
            childName,
            date_of_birth,
            gender,
            birthWeight,
            birthHeight,
            fatherName,
            motherName: parentName, // parent-name field contains mother's name
            birthFacility,
            birthAttendant,
            email: parentEmail,
            phoneNo: formattedPhone, // Use formatted phone number
            address: {
                street,
                ward,
                Region: district, // Using 'district' from HTML form
            },
            motherAge,
            health_care_provider_id: healthcareProviderId,
        };

        // Add this before the fetch call
        console.log("Sending data:", {
            url: "http://localhost:5173/api/healthcare/children",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                Authorization: `Bearer ${authToken}`,
            },
            body: data,
        });

        try {
            const response = await fetch(
                "http://localhost:5173/api/healthcare/children",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        Authorization: `Bearer ${authToken}`,
                    },
                    body: JSON.stringify(data),
                }
            );

            console.log("Response status:", response.status);
            console.log("Response headers:", response.headers);

            // Get response text first
            const responseText = await response.text();
            console.log("Raw response text:", responseText);

            // Try to parse as JSON
            let responseData;
            try {
                responseData = JSON.parse(responseText);
                console.log("Parsed response data:", responseData);
            } catch (parseError) {
                console.error("JSON Parse Error:", parseError);
                console.error("Response was not valid JSON:", responseText);
                throw new Error(
                    `Server returned invalid JSON response: ${responseText.substring(
                        0,
                        200
                    )}...`
                );
            }

            if (!response.ok) {
                console.error("Server Error Response:", responseData);
                const errorMessage =
                    responseData.message ||
                    responseData.error ||
                    `HTTP ${response.status} Error`;
                throw new Error(errorMessage);
            }

            // Success case - show simple success message
            console.log("Registration successful:", responseData);
            showSuccessMessage("Child registered successfully!");

            // Reset form after successful registration
            registerForm.reset();

            // Auto-fill the next child number for the next registration
            setTimeout(() => {
                autoFillChildNumber();
            }, 1000);
        } catch (error) {
            console.error("Registration Error:", error);

            // Check if it's a network error
            if (error instanceof TypeError && error.message.includes("fetch")) {
                showErrorMessage(
                    "Network error: Unable to connect to server. Please check your connection and try again."
                );
            } else {
                showErrorMessage(`Registration failed: ${error.message}`);
            }
        }
    });
});
