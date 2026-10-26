<?php

function renderHeader(
    string $pageTitle = 'Quản lý kho may mặc',
    string $userName = 'Người dùng',
    string $roleName = '',
    string $baseUrl = '/TheStars_QLKHMM'
): void {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <title>
            <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        </title>

        <link
            rel="stylesheet"
            href="<?= htmlspecialchars(
                rtrim($baseUrl, '/') . '/assets/css/layout.css',
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </head>

    <body>
        <header class="app-header">
            <div class="app-header-title">
                <?= htmlspecialchars(
                    $pageTitle,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="app-account">
                <span class="app-avatar" aria-hidden="true">○</span>

                <div>
                    <div class="app-account-name">
                        <?= htmlspecialchars(
                            $userName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div class="app-account-role">
                        <?= htmlspecialchars(
                            $roleName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                </div>
            </div>
        </header>
    <?php
}