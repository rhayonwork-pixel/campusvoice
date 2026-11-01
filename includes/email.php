<?php
/**
 * Branded HTML emails. Email apps ignore <style> blocks and external CSS, so everything is table-based with
 * INLINE styles (navy + gold, serif headings to match the app). Every email also gets a plain-text twin (see mail.php).
 */

/** Absolute site URL (emails need full URLs): https://yourdomain/CampusVoice */
function base_url(): string
{
    return (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
}

/**
 * Wrap content in the CampusVoice email layout.
 * $o: title, preheader, heading, paragraphs[] (trusted HTML), button ['label'=>, 'url'=>] (optional), note (optional text)
 */
function email_layout(array $o): string
{
    $navy = '#0f2a5e'; $gold = '#d9a441'; $ink = '#1a2230'; $muted = '#66758a';
    $serif = "Georgia,'Times New Roman',serif"; $sans = "Arial,Helvetica,sans-serif";
    $logo = base_url() . '/assets/img/cmu-logo-email.png';
    $address = setting('contact_address', 'Pampano cor. Maya-Maya Sts., Longos, City of Malabon');
    $title = e($o['title'] ?? 'CampusVoice');
    $pre = e($o['preheader'] ?? '');

    $paras = '';
    foreach (($o['paragraphs'] ?? []) as $p) {
        $paras .= '<p style="margin:0 0 16px;font-family:' . $sans . ';font-size:15px;line-height:1.6;color:' . $ink . ';">' . $p . '</p>';
    }

    $button = '';
    if (!empty($o['button'])) {
        $url = e($o['button']['url']);
        // "bulletproof" button: a table cell with the colour on the cell, so it survives Outlook and Gmail
        $button = '<tr><td style="padding:8px 32px 8px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td align="center" bgcolor="' . $navy . '" style="border-radius:8px;background:' . $navy . ';">'
            . '<a href="' . $url . '" target="_blank" style="display:inline-block;padding:14px 28px;font-family:' . $sans . ';font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">' . e($o['button']['label']) . '</a>'
            . '</td></tr></table></td></tr>'
            . '<tr><td style="padding:16px 32px 0;font-family:' . $sans . ';font-size:12px;line-height:1.5;color:' . $muted . ';">Button not working? Copy and paste this link into your browser:<br>'
            . '<a href="' . $url . '" target="_blank" style="color:' . $navy . ';word-break:break-all;">' . $url . '</a></td></tr>';
    }

    $note = '';
    if (!empty($o['note'])) {
        $note = '<tr><td style="padding:24px 32px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
            . '<td bgcolor="#fbf8f1" style="background:#fbf8f1;border-radius:8px;padding:14px 16px;font-family:' . $sans . ';font-size:13px;line-height:1.55;color:#44536a;">' . e($o['note']) . '</td></tr></table></td></tr>';
    }

    return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light">'
        . '<title>' . $title . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f4f1ea;">'
        // hidden preview text shown next to the subject in the inbox
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#f4f1ea;font-size:1px;line-height:1px;">' . $pre . str_repeat('&nbsp;&zwnj;', 40) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f1ea;"><tr><td align="center" style="padding:32px 16px;">'
        . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;background:#ffffff;border:1px solid #e3d9bf;border-radius:12px;">'
        // header band
        . '<tr><td style="background:' . $navy . ';border-radius:11px 11px 0 0;padding:24px 32px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td valign="middle"><img src="' . e($logo) . '" height="56" alt="CMU" style="display:block;height:56px;width:auto;border:0;"></td>'
        . '<td valign="middle" style="padding-left:16px;font-family:' . $serif . ';font-size:22px;font-weight:bold;line-height:1.15;color:#ffffff;">CampusVoice<br>'
        . '<span style="font-family:' . $sans . ';font-size:11px;font-weight:normal;letter-spacing:2px;text-transform:uppercase;color:' . $gold . ';">City of Malabon University</span></td>'
        . '</tr></table></td></tr>'
        // body
        . '<tr><td style="padding:32px 32px 8px;">'
        . '<h1 style="margin:0 0 16px;font-family:' . $serif . ';font-size:24px;line-height:1.25;color:' . $navy . ';">' . e($o['heading'] ?? '') . '</h1>' . $paras . '</td></tr>'
        . $button . $note
        // footer
        . '<tr><td style="padding:32px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-top:1px solid #efe7d5;padding-top:16px;font-family:' . $sans . ';font-size:12px;line-height:1.6;color:' . $muted . ';">'
        . 'This message was sent by CampusVoice, the concern management system of City of Malabon University.<br>' . e($address) . '</td></tr></table></td></tr>'
        . '</table>'
        . '<p style="margin:16px 0 0;font-family:' . $sans . ';font-size:11px;color:#8a8576;">&copy; ' . date('Y') . ' CampusVoice</p>'
        . '</td></tr></table></body></html>';
}

/** Password-reset email. Returns [subject, html]. */
function email_password_reset(array $user, string $link, int $minutes): array
{
    return ['Reset your CampusVoice password', email_layout([
        'title'      => 'Reset your password',
        'preheader'  => 'Use this link to choose a new password. It expires in ' . $minutes . ' minutes.',
        'heading'    => 'Reset your password',
        'paragraphs' => [
            'Hi ' . e($user['first_name']) . ',',
            'We received a request to reset the password for your CampusVoice account. Press the button below to choose a new one.',
        ],
        'button'     => ['label' => 'Reset my password', 'url' => $link],
        'note'       => 'This link works once and expires in ' . $minutes . ' minutes. If you did not ask for this, you can safely ignore this email — your password will not change.',
    ])];
}

/** Test email sent from Settings so the admin can confirm Gmail works. Returns [subject, html]. */
function email_test(array $user, string $via): array
{
    return ['CampusVoice test email', email_layout([
        'title'      => 'Test email',
        'preheader'  => 'Your CampusVoice email setup is working.',
        'heading'    => 'Email is working',
        'paragraphs' => [
            'Hi ' . e($user['first_name']) . ',',
            'This is a test message from your CampusVoice system. If you can read it, password-reset emails and other notices can reach students and staff.',
            'Sent using: <b>' . e($via) . '</b>.',
        ],
        'note'       => 'You can delete this message. Nothing else was changed.',
    ])];
}
