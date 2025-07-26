<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bank Management System</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="header">
        <h1>Bank Management System</h1>
        <h2>Your Trusted Financial Partner</h2>
    </div>
    <h3>
        Customer Registration Form
    </h3>
    <div class="form">
        <div class="content">
            <div class="form-field">
                <label for="fullName">
                    Full Name:
                </label>
                <input class="form-input" type="text" id="fullName" name="fullName">
            </div>
            <div class="form-field">
                <label for="dob">
                    Date of Birth:
                </label>
                <input class="form-input" type="date" id="dob" name="dob">
            </div>
            <div class="form-field">
                <label for="gender">
                    Gender:
                </label>
                <div class="form-input">
                    <label>
                        <input type="radio" name="gender" value="male"> Male
                    </label>
                    <label>
                        <input type="radio" name="gender" value="female"> Female
                    </label>
                    <label>
                        <input type="radio" name="gender" value="other"> Other
                    </label>
                </div>
            </div>
            <div class="form-field">
                <label for="maritalStatus">
                    Marital Status:
                </label>
                <div class="form-input">
                    <select id="maritalStatus" name="maritalStatus">
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="not_specified">Not Specified</option>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <label for="accType">
                    Account Type:
                </label>
                <div class="form-input">
                    <select id="accType" name="accType">
                        <option value="savings">Savings</option>
                        <option value="current">Current</option>
                        <option value="fixed_deposit">Fixed Deposit</option>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <label for="initial-deposit">
                    Initial Deposit Amount:
                </label>
                <input class="form-input" type="number" id="initial-deposit" name="initial-deposit">
            </div>
            <div class="form-field">
                <label for="mobile">
                    Mobile Number:
                </label>
                <input class="form-input" type="tel" id="mobile" name="mobile">
            </div>
            <div class="form-field">
                <label for="email">
                    Email Address:
                </label>
                <input class="form-input" type="email" id="email" name="email">
            </div>
            <div class="form-field">
                <label for="address">
                    Address:
                </label>
                <textarea class="form-input" id="address" name="address"></textarea>
            </div>
            <div class="form-field">
                <label for="occupation">
                    Occupation:
                </label>
                <input class="form-input" type="text" id="occupation" name="occupation">
            </div>
            <div class="form-field">
                <label for="national-id">
                    National ID:
                </label>
                <input class="form-input" type="text" id="national-id" name="national-id">
            </div>
            <div class="form-field">
                <label for="password">
                    Set Password:
                </label>
                <input class="form-input" type="password" id="password" name="password">
            </div>
            <div class="form-field">
                <label for="id-proof">
                    Upload ID Proof:
                </label>
                <input class="form-input" type="file" id="id-proof" name="id-proof">
            </div>
        </div>
        <div class="form-field agreement">
            <label>
                <input id="agree" type="checkbox" name="agreement" required> I agree to the terms and conditions
            </label>
        </div>
        <div class="button-group">
            <button type="submit">Register</button>
            <button type="reset">Clear</button>
        </div>
    </div>
    <div class="overflow">
        This is a demo text to show how overflow works in a small container with some content.
    </div>
</body>
</html>