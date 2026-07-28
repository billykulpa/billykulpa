<?php
/**
 * Contact form handling: Cloudflare Turnstile + honeypot, then mail.
 * mail_mode 'log' writes to logs/mail.log (local dev); 'mail' uses PHP mail()
 * (fine on Hostinger). If deliverability ever needs SMTP, PHPMailer drops in
 * here without touching the form.
 */

function contact_route(): void
{
    $pg = page('contact');
    $sent = false; $error = ''; $old = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $old = compact('name', 'email', 'message');

        if ($_POST['website'] ?? '') {
            // Honeypot tripped: pretend success, tell no one.
            $sent = true;
        } elseif ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please fill in every field (with a valid email).';
        } elseif (getenv('SKIP_TURNSTILE') !== '1'   // local-dev bypass: SKIP_TURNSTILE=1 php -S ...
                  && !turnstile_ok($_POST['cf-turnstile-response'] ?? '')) {
            $error = 'The spam check did not pass — please try again.';
        } else {
            $ok = contact_send($name, $email, $message);
            if ($ok) { $sent = true; }
            else { $error = 'Something went wrong sending your message. Email me directly instead: billy@billykulpa.com'; }
        }
    }

    render('contact', [
        'pg' => [
            'h1' => $pg['h1'] ?: 'Hello there.',
            'meta_title' => $pg['meta_title'] ?: 'Contact — Billy Kulpa',
            'meta_description' => $pg['meta_description'] ?: 'Get in touch with Billy Kulpa.',
        ],
        'sent' => $sent, 'error' => $error, 'old' => $old, 'nav' => 'contact',
    ]);
}

function turnstile_ok(string $token): bool
{
    $secret = config()['turnstile']['secret_key'];
    if ($token === '') return false;
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        'timeout' => 10,
    ]]);
    $res = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($res === false) return false;
    $data = json_decode($res, true);
    return !empty($data['success']);
}

function contact_send(string $name, string $email, string $message): bool
{
    $c = config();
    $to      = $c['contact']['to'];
    $subject = 'billykulpa.com contact: ' . mb_substr($name, 0, 60);
    $body    = "Name: $name\nEmail: $email\n\n$message\n";

    if (($c['contact']['mail_mode'] ?? 'log') === 'log') {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        return (bool) file_put_contents(
            $dir . '/mail.log',
            '[' . date('c') . "] TO: $to\nSUBJECT: $subject\n$body\n---\n",
            FILE_APPEND
        );
    }
    $headers = 'From: no-reply@billykulpa.com' . "\r\n"
             . 'Reply-To: ' . $email . "\r\n"
             . 'X-Mailer: PHP/' . PHP_VERSION;
    return mail($to, $subject, $body, $headers);
}
