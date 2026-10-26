<?php
$baseUrl = '/TheStars_QLKHMM';
return [
    'baseUrl' => $baseUrl,
    'roles' => [
        'KIEM_DINH' => [
            'roleName' => 'Bộ phận Kiểm định',
            'items' => [
                ['key' => 'trang-chu', 'label' => 'Trang chủ', 'url' => $baseUrl . '/pages/trang-chu/index.php', 'icon' => '⌂'],
                ['key' => 'kiem-dinh', 'label' => 'Phiếu kiểm định', 'url' => $baseUrl . '/pages/kiem-dinh/index.php', 'icon' => '▤'],
            ],
        ],
        'QUAN_LY_KHO' => [
            'roleName' => 'Quản lý kho',
            'items' => [
                ['key' => 'trang-chu', 'label' => 'Trang chủ', 'url' => $baseUrl . '/pages/trang-chu/index.php', 'icon' => '⌂'],
            ],
        ],
        'NHAN_VIEN_KHO' => [
            'roleName' => 'Nhân viên kho',
            'items' => [
                ['key' => 'trang-chu', 'label' => 'Trang chủ', 'url' => $baseUrl . '/pages/trang-chu/index.php', 'icon' => '⌂'],
            ],
        ],
        'MUA_HANG' => [
            'roleName' => 'Bộ phận mua hàng',
            'items' => [
                ['key' => 'trang-chu', 'label' => 'Trang chủ', 'url' => $baseUrl . '/pages/trang-chu/index.php', 'icon' => '⌂'],
            ],
        ],
        'KHSX' => [
            'roleName' => 'Bộ phận Kế hoạch sản xuất',
            'items' => [
                ['key' => 'trang-chu', 'label' => 'Trang chủ', 'url' => $baseUrl . '/pages/trang-chu/index.php', 'icon' => '⌂'],
            ],
        ],
    ],
    'bottomItems' => [],
];
