
function isAlpha(str) {
    for (var i = 0; i < str.length; i++) {
        if ((str[i] < 'A' || str[i] > 'Z') && (str[i] < 'a' || str[i] > 'z')) {
            return false;
        }
    }
    return true;
}

function hasUpperCase(str) {
    var hasUppercase = false;
    for (var i = 0; i < str.length; i++) {
        if (str[i] >= 'A' && str[i] <= 'Z') {
            hasUppercase = true;
            break;
        }
    }
    return hasUppercase;
}

function hasLowerCase(str) {
    var hasLowercase = false;
    for (var i = 0; i < str.length; i++) {
        if (str[i] >= 'a' && str[i] <= 'z') {
            hasLowercase = true;
            break;
        }
    }
    return hasLowercase;
}

function hasNumber(str) {
    var hasNumber = false;
    for (var i = 0; i < str.length; i++) {
        if (str[i] >= '0' && str[i] <= '9') {
            hasNumber = true;
            break;
        }
    }
    return hasNumber;
}

function validPhoneNumber(phone) {
    if (phone.length !== 11) {
        return false;
    }

    for (var i = 0; i < phone.length; i++) {
        if (phone[i] < '0' || phone[i] > '9') {
            return false;
        }
    }
    return true;
}

function hasSpecialChar(str) {
    var specialChars = "!@#$%^&*()_+[]{}|;:',.<>?/";
    var hasSpecialChar = false;
    for (var i = 0; i < str.length; i++) {
        for (var j = 0; j < specialChars.length; j++) {
            if (str[i] === specialChars[j]) {
                hasSpecialChar = true;
                break;
            }
        }
    }
    return hasSpecialChar;
}

function validateForm() {
    var firstName = document.getElementById("firstName").value;
    var lastName = document.getElementById("lastName").value;
    var phone = document.getElementById("phone").value;
    var email = document.getElementById("email").value;
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirmPassword").value;
    
    if (firstName == "" || lastName == "" || phone == "" || email == "" || password == "" || confirmPassword == "") {
        alert("All fields must be filled out.");
        return false;
    }

    if (!isAlpha(firstName) || !isAlpha(lastName)) {
        alert("Name must contain only alphabets (A-Z, a-z).");
        return false;
    }

    if (!validPhoneNumber(phone)) {
        alert("Phone number must be exactly 11 digits.");
        return false;
    }


    if (password.length < 8) {
        alert("Password must be at least 8 characters long.");
        return false;
    }

    if (!hasUpperCase(password) || !hasLowerCase(password) || !hasNumber(password) || !hasSpecialChar(password)) {
        alert("Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.");
        return false;
    }

    if (password != confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    var state = document.getElementById("state").value;

    if (!state) {
        alert("Select a state!");
        return false;
    }

    var donationAmount = document.getElementsByName("donationAmount");
    var amountSelected = false;
    for (var i = 0; i < donationAmount.length; i++) {
        if (donationAmount[i].checked) {
            amountSelected = true;
            break;
        }
    }

    //12345678Aa;.

    var otherAmount = document.getElementById("otherAmount").value;

    // if neither amountSelected nor otherAmount is provided
    if (!amountSelected && !otherAmount) {
        alert("Please select a donation amount or provide a custom amount.");
        return false;
    }

    if (amountSelected && otherAmount) {
        alert("Please select a donation amount or provide a custom amount, not both.");
        return false;
    }

    var regular = document.getElementById("regularBasis").checked;

    var ann = document.getElementById("anonymous").checked;
    var gift = document.getElementById("matchingGift").checked;
    var noThankYou = document.getElementById("noThankYou").checked;

    var comments = document.getElementById("comments").value;
    
    alert("Form submitted successfully!");
    return true;
}