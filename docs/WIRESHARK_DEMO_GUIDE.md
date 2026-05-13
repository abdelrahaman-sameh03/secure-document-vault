# Wireshark HTTP vs HTTPS Demonstration Guide

## Goal

Show why HTTPS is important by comparing readable HTTP traffic with encrypted HTTPS/TLS traffic.

## Part 1: HTTP Capture

1. Run the app using HTTP:

```text
http://localhost/secure_document_vault_xampp/public/login.php
```

2. Open Wireshark.
3. Select the loopback adapter or the active network interface.
4. Start capture.
5. Use this filter:

```text
http
```

6. Login with a demo account.
7. Look for HTTP POST requests.
8. Take a screenshot showing readable traffic.

## Part 2: HTTPS Capture

1. Configure HTTPS in XAMPP.
2. Run the app using HTTPS:

```text
https://localhost/secure_document_vault_xampp/public/login.php
```

3. Capture traffic again in Wireshark.
4. Use this filter:

```text
tls
```

5. Take a screenshot showing encrypted TLS traffic.

## Explanation

In HTTP, data can appear in readable plain text. In HTTPS, TLS encrypts the communication between the browser and the server, so attackers cannot easily read sensitive data such as passwords, JWT tokens, or uploaded document content.
