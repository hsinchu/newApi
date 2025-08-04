@echo off
echo 启动奖池WebSocket服务器...
echo.

REM 检查Node.js是否安装
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误: 未找到Node.js，请先安装Node.js
    echo 下载地址: https://nodejs.org/
    pause
    exit /b 1
)

REM 检查是否已安装依赖
if not exist node_modules (
    echo 正在安装依赖...
    npm install
    if %errorlevel% neq 0 (
        echo 错误: 依赖安装失败
        pause
        exit /b 1
    )
)

REM 启动WebSocket服务器
echo 正在启动WebSocket服务器...
echo WebSocket地址: ws://localhost:8080
echo HTTP推送地址: http://localhost:8080/api/push-prize-pool
echo 健康检查地址: http://localhost:8080/health
echo 状态查看地址: http://localhost:8080/status
echo.
echo 按 Ctrl+C 停止服务器
echo.

npm start

echo.
echo WebSocket服务器已停止
pause