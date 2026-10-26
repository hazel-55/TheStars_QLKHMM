<?php

function renderSidebar(
    array $menuItems,
    string $activeMenu = '',
    string $roleName = '',
    array $bottomItems = []
): void {
    ?>
    <aside class="app-sidebar">
        <div class="app-brand">
            <span class="app-brand-icon" aria-hidden="true">◇</span>
            <span>KHO MAY MẶC</span>
        </div>

        <div class="app-menu-label">
            KHÔNG GIAN LÀM VIỆC
        </div>

        <nav class="app-menu" aria-label="Menu nghiệp vụ">
            <?php foreach ($menuItems as $item): ?>
                <a
                    class="app-menu-link <?= $activeMenu === $item['key']
                        ? 'active'
                        : '' ?>"
                    href="<?= htmlspecialchars(
                        $item['url'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    <?= $activeMenu === $item['key']
                        ? 'aria-current="page"'
                        : '' ?>
                >
                    <span class="app-menu-icon" aria-hidden="true">
                        <?= htmlspecialchars(
                            $item['icon'] ?? '•',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <span>
                        <?= htmlspecialchars(
                            $item['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="app-menu-bottom">
            <nav class="app-menu" aria-label="Menu tài khoản">
                <?php foreach ($bottomItems as $item): ?>
                    <a
                        class="app-menu-link <?= $activeMenu === $item['key']
                            ? 'active'
                            : '' ?>"
                        href="<?= htmlspecialchars(
                            $item['url'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        <?= $activeMenu === $item['key']
                            ? 'aria-current="page"'
                            : '' ?>
                    >
                        <span class="app-menu-icon" aria-hidden="true">
                            <?= htmlspecialchars(
                                $item['icon'] ?? '•',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $item['label'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="app-sidebar-role">
                <?= htmlspecialchars(
                    $roleName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        </div>
    </aside>
    <?php
}