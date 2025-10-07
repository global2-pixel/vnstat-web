<?php
// --- 后端数据处理 ---

// 设置时区以避免时间错误
date_default_timezone_set('Asia/Shanghai');

/**
 * 将字节转换为更易读的格式 (KB, MB, GB, TB).
 * @param int $bytes 要转换的字节数.
 * @param int $precision 小数点后的位数.
 * @return string 格式化后的字符串.
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    if ($bytes === 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 初始化变量
$interface_name = 'N/A';
$today_rx = 0;
$today_tx = 0;
$month_rx = 0;
$month_tx = 0;
$error_message = null;
$last_updated = 'N/A';

// 【重要修改】使用兼容性更好的 `vnstat --json` 命令
// 通过 2>&1 将标准错误重定向到标准输出，以便捕获任何错误信息
$json_output = shell_exec('vnstat --json 2>&1');

if ($json_output) {
    $data = json_decode($json_output);

    // 检查 JSON 是否有效且包含所需的数据
    if (json_last_error() === JSON_ERROR_NONE && !empty($data->interfaces)) {
        // 通常我们只关心第一个网络接口
        $interface = $data->interfaces[0];
        $interface_name = $interface->id;
        $last_updated = date('Y-m-d H:i:s', $interface->updated->timestamp);

        // 获取今天的数据
        if (!empty($interface->traffic->day)) {
            $today = $interface->traffic->day[0];
            $today_rx = $today->rx;
            $today_tx = $today->tx;
        }

        // 获取本月的数据
        if (!empty($interface->traffic->month)) {
            $month = $interface->traffic->month[0];
            $month_rx = $month->rx;
            $month_tx = $month->tx;
        }

    } else {
        // 如果还是解析失败，显示原始输出以供调试
        $error_message = '无法解析 vnstat 的输出。请检查 vnstat JSON 输出格式是否正确。<br><pre class="mt-2 text-left text-xs bg-black/50 p-3 rounded-md overflow-x-auto">' . htmlspecialchars($json_output) . '</pre>';
    }
} else {
    $error_message = '无法执行 "vnstat --json" 命令。<br>请确认：<br>1. vnstat 已安装并正在运行。<br>2. PHP 的 `shell_exec` 函数已启用。<br>3. Web 服务器用户有权限执行 vnstat。';
}

// 计算总计
$today_total = $today_rx + $today_tx;
$month_total = $month_rx + $month_tx;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>vnstat 网络流量监控</title>
    <!-- 引入 Tailwind CSS 以快速构建现代化界面 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 引入 Google Fonts 字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('https://t.alcy.cc/pc');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        /* 自定义模糊背景效果 */
        .backdrop-blur-xl {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-2xl mx-auto">
        <!-- 主信息面板 -->
        <div class="bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-6 md:p-8">
                <!-- 头部信息 -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold">网络流量监控</h1>
                        <p class="text-gray-300">接口: <span class="font-semibold text-green-400"><?= htmlspecialchars($interface_name) ?></span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-400">最后更新</p>
                        <p class="text-md font-medium text-gray-200"><?= $last_updated ?></p>
                    </div>
                </div>

                <?php if ($error_message): ?>
                <!-- 错误信息显示 -->
                <div class="bg-red-500/30 border border-red-500 text-red-200 px-4 py-3 rounded-lg" role="alert">
                    <strong class="font-bold">出错啦！</strong>
                    <div class="block sm:inline"><?= $error_message ?></div>
                </div>
                <?php else: ?>
                <!-- 流量数据展示 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 今日流量 -->
                    <div class="bg-gray-900/40 p-5 rounded-xl border border-gray-700/60">
                        <h2 class="text-xl font-semibold mb-4 text-gray-100">今日流量</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-baseline">
                                <span class="text-gray-400">接收 (RX)</span>
                                <span class="text-lg font-medium text-blue-300"><?= formatBytes($today_rx) ?></span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-gray-400">发送 (TX)</span>
                                <span class="text-lg font-medium text-purple-300"><?= formatBytes($today_tx) ?></span>
                            </div>
                            <hr class="border-gray-700 my-2">
                            <div class="flex justify-between items-baseline">
                                <span class="font-bold text-gray-300">总计</span>
                                <span class="text-xl font-bold text-green-400"><?= formatBytes($today_total) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 本月流量 -->
                    <div class="bg-gray-900/40 p-5 rounded-xl border border-gray-700/60">
                        <h2 class="text-xl font-semibold mb-4 text-gray-100">本月流量</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-baseline">
                                <span class="text-gray-400">接收 (RX)</span>
                                <span class="text-lg font-medium text-blue-300"><?= formatBytes($month_rx) ?></span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-gray-400">发送 (TX)</span>
                                <span class="text-lg font-medium text-purple-300"><?= formatBytes($month_tx) ?></span>
                            </div>
                            <hr class="border-gray-700 my-2">
                            <div class="flex justify-between items-baseline">
                                <span class="font-bold text-gray-300">总计</span>
                                <span class="text-xl font-bold text-green-400"><?= formatBytes($month_total) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- 页脚 -->
            <div class="bg-black/20 px-6 py-3 text-center text-xs text-gray-400">
                Powered by PHP <?= phpversion() ?> on <?= php_uname('s') ?>
            </div>
        </div>
    </div>

</body>
</html>

