<?php

require_once __DIR__ . '../../../services/SettingService.php';

function mojo_email_header()
{
    $fonts = '<!--[if !mso]><!-->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
        <style type="text/css">
            @import url("https://fonts.googleapis.com/css2?family=Poppins&display=swap");
        </style>
        <!--<![endif]-->
        <!--[if mso]>
        <style type="text/css">
            .poppins, table, td, a, span, p, li, ul, ol { font-family: "Poppins", Helvetica, sans-serif; color:black; font-weight: 400; font-style: normal; }
            table, td, a, span, p, li, ul, ol{font-size:15px;}
        </style>
        <![endif]-->';

    return '<!DOCTYPE html>
    <html>
    <head>
    ' . $fonts . '
    </head>
    <body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color: #F6F9FC;padding:50px 0;">
        <tbody>
            <tr style="text-align:center;">
            <table width="600" align="center" cellpadding="0" cellspacing="0" border="0" class="poppins" style="background-color:#ffffff; font-family:\'Poppins\', Helvetica, sans-serif; margin:0 auto;background-color: #ffffff;color:black;">
                <tr>
                    <td align="center" style="padding: 20px 0;background:#60C0A8;">
                        <img src="' . LOGO_MAIL . '" alt="Mojo Sharing" style="max-width: 132.4px;vertical-align:top;">
                    </td>
                </tr>
                <tr>
                    <td class="poppins" style="padding:20px 6.4% 50px;text-align:left; font-family:\'Poppins\', Helvetica, sans-serif; line-height:1.5;font-size:15px;">';
}

function mojo_email_footer()
{
    $contacts = (new SettingService())->getContacts(1);

    $template = '</td>
                </tr>
                <tr>
                    <td style="padding:25px 6.4%; background-color:#60C0A8; font-family:\'Poppins\', Helvetica, sans-serif;">';
    if ($contacts) {
        $template .= '<table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="text-align:left;">';
        if (!empty($contacts->facebook)) {
            $template .= '<a target="_blank" href="' . $contacts->facebook . '" style="width:100%;max-width:20px;height:20px;display:inline-block;margin-right:12px;">
                                            <img src="' . FB_ICON . '" alt="Facebook" style="width:100%;height:100%;object-fit:contain;object-position:center;filter:brightness(100);">
                                        </a>';
        }
        if (!empty($contacts->instagram)) {
            $template .= '<a target="_blank" href="' . $contacts->instagram . '" style="width:100%;max-width:20px;height:20px;display:inline-block;margin-right:12px;">
                                            <img src="' . IG_ICON . '" alt="Instagram" style="width:100%;height:100%;object-fit:contain;object-position:center;filter:brightness(100);">
                                        </a>';
        }
        if (!empty($contacts->linkedin)) {
            $template .= '<a target="_blank" href="' . $contacts->linkedin . '" style="width:100%;max-width:20px;height:20px;display:inline-block;margin-right:12px;">
                                            <img src="' . IN_ICON . '" alt="LinkedIn" style="width:100%;height:100%;object-fit:contain;object-position:center;filter:brightness(100);">
                                        </a>';
        }
        if (!empty($contacts->youtube)) {
            $template .= '<a target="_blank" href="' . $contacts->youtube . '" style="width:100%;max-width:20px;height:20px;display:inline-block;">
                                            <img src="' . YT_ICON . '" alt="YouTube" style="width:100%;height:100%;object-fit:contain;object-position:center;filter:brightness(100);">
                                        </a>';
        }
        $template .= '  </td>
                                <td style="text-align: center;">';
        if (!empty($contacts->mail_footer)) {
            $template .= '<a href="mailto:' . $contacts->mail_footer . '" style="color:#ffffff; text-decoration:none; font-size:12px; font-family:\'Poppins\', Helvetica, sans-serif;">' . $contacts->mail_footer . '</a>';
        }
        $template .= '  </td>
                                <td style="text-align: right;">';
        if (!empty($contacts->phone_footer)) {
            $template .= '<a href="tel:' . $contacts->phone_footer . '" style="color:#ffffff; text-decoration:none; font-size:12px; font-family:\'Poppins\', Helvetica, sans-serif;">' . $contacts->phone_footer . '</a>';
        }
        $template .= '  </td>
                            </tr>
                            <tr>';
        if (!empty($contacts->direction_footer)) {
            $template .= '<td colspan="3" style="text-align:center;color:#ffffff;padding-top:12px;font-size:12px; font-family:\'Poppins\', Helvetica, sans-serif;">' . $contacts->direction_footer . '</td>';
        }
        $template .= '</tr>
                        </table>';
    }
    $template .= '</td>
                </tr>
            </table>
            </tr>
        </tbody>
    </table>
    </body>
    </html>';

    return $template;
}
