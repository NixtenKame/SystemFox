<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../public/images/favicon.ico">
    <title>Error 403 - Forbidden</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            color: #333;
            text-align: center;
            padding: 50px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: #d9534f;
        }
        p {
            font-size: 18px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
        .icon {
            font-size: 100px;
            color: #d9534f;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            font-size: 18px;
            color: #fff;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .icon {
            margin: auto;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var hostname = window.location.hostname;
            var homepageButton = document.getElementById("homepageButton");
            var redirectButton = document.getElementById("redirectButton");
            var errorMessage = document.getElementById("errorMessage");
            var errorIcon = document.getElementById("errorIcon");

            if (hostname === "nixten.ddns.net") {
                homepageButton.style.display = "inline-block";
                errorMessage.innerHTML = `
                    <p>You may have encountered this error because you do not have permission to access this page.</p>
                    <p>Possible reasons include:</p>
                    <ul>
                        <li>You are not logged in with the correct credentials.</li>
                        <li>Your account does not have the necessary permissions to view this page.</li>
                        <li>The page you are trying to access is restricted to certain users.</li>
                    </ul>
                    <p>Steps to resolve:</p>
                    <ul>
                        <li>Ensure you are logged in with the correct credentials.</li>
                        <li>Contact the site administrator to request access permissions.</li>
                        <li>Verify that your account has the necessary permissions to view this page.</li>
                    </ul>
                    <p>Please contact the site administrator if you believe this is an error.</p>
                `;
                errorIcon.innerHTML = '<img src="../public/images/Error 403.png" width="200" height="auto" class="icon">';
            } else {
                redirectButton.style.display = "inline-block";
                errorMessage.innerHTML = `
                    <p>You may have encountered this error because you are using the IP address of the site.</p>
                    <p>Possible reasons include:</p>
                    <ul>
                        <li>The site is configured to restrict access via IP address.</li>
                        <li>You are trying to access a restricted page directly via IP.</li>
                    </ul>
                    <p>Steps to resolve:</p>
                    <ul>
                        <li>Access the site using the domain name <strong>nixten.ddns.net</strong>.</li>
                        <li>Contact the site administrator if you believe this is an error.</li>
                    </ul>
                `;
                errorIcon.innerHTML = "🌐";
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div id="errorIcon" class="icon">🚫</div>
        <h1>Error 403</h1>
        <p>Forbidden - You don't have permission to access this page.</p>
        <div id="errorMessage">
            <p>You may have encountered this error if you are using the IP address of the site or you do not have permission to use the webpage you are trying to access.</p>
        </div>
        <a id="homepageButton" class="button" style="display: none;" href="/">Go to Homepage</a>
        <a id="redirectButton" class="button" style="display: none;" href="https://nixten.ddns.net">Go to the Correct Website</a>
    </div>
</body>
</html>