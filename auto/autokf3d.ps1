# 设置输出编码为UTF-8
$OutputEncoding = [console]::InputEncoding = [console]::OutputEncoding = New-Object System.Text.UTF8Encoding
# 设置工作目录
Set-Location "E:\www\apiApp\api"

# 循环执行
while ($true) {
    # 执行ThinkPHP命令
    php think autodraw 3d
    
    Start-Sleep -Seconds 5
}