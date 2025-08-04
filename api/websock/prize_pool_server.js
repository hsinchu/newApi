const WebSocket = require('ws');
const express = require('express');
const http = require('http');

// 创建Express应用
const app = express();
const server = http.createServer(app);

// 创建WebSocket服务器
const wss = new WebSocket.Server({ server });

// 存储连接的客户端
const clients = new Map();

// 中间件解析JSON
app.use(express.json());

// WebSocket连接处理
wss.on('connection', (ws, req) => {
    console.log('新的WebSocket连接建立');
    
    // 为每个连接分配一个唯一ID
    const clientId = Date.now() + Math.random();
    clients.set(clientId, {
        ws: ws,
        subscriptions: new Set()
    });
    
    // 处理客户端消息
    ws.on('message', (message) => {
        try {
            // 处理心跳ping消息
            if (message.toString() === 'ping') {
                ws.send('pong');
                console.log(`客户端 ${clientId} 心跳检测`);
                return;
            }
            
            const data = JSON.parse(message);
            const client = clients.get(clientId);
            
            if (data.type === 'subscribe' && data.lottery_code) {
                // 订阅特定彩种的奖池更新
                client.subscriptions.add(data.lottery_code);
                console.log(`客户端 ${clientId} 订阅了 ${data.lottery_code} 的奖池更新`);
                
                // 发送订阅确认
                ws.send(JSON.stringify({
                    type: 'subscribe_success',
                    lottery_code: data.lottery_code,
                    message: '订阅成功'
                }));
            }
        } catch (error) {
            console.error('解析WebSocket消息失败:', error);
        }
    });
    
    // 连接关闭处理
    ws.on('close', () => {
        console.log(`WebSocket连接 ${clientId} 已关闭`);
        clients.delete(clientId);
    });
    
    // 错误处理
    ws.on('error', (error) => {
        console.error(`WebSocket连接 ${clientId} 发生错误:`, error);
        clients.delete(clientId);
    });
});

// HTTP接口：接收奖池更新推送
app.post('/api/push-prize-pool', (req, res) => {
    try {
        const message = req.body;
        console.log('收到奖池更新推送:', message);
        
        // 验证消息格式
        if (!message.type || !message.lottery_code || !message.bet_amount) {
            return res.status(400).json({ error: '消息格式不正确' });
        }
        
        // 广播给订阅了该彩种的所有客户端
        let sentCount = 0;
        clients.forEach((client, clientId) => {
            if (client.subscriptions.has(message.lottery_code) && 
                client.ws.readyState === WebSocket.OPEN) {
                try {
                    client.ws.send(JSON.stringify(message));
                    sentCount++;
                } catch (error) {
                    console.error(`发送消息给客户端 ${clientId} 失败:`, error);
                }
            }
        });
        
        console.log(`奖池更新消息已发送给 ${sentCount} 个客户端`);
        res.json({ 
            success: true, 
            message: '奖池更新推送成功',
            sent_count: sentCount
        });
        
    } catch (error) {
        console.error('处理奖池更新推送失败:', error);
        res.status(500).json({ error: '服务器内部错误' });
    }
});

// 健康检查接口
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        clients: clients.size,
        timestamp: new Date().toISOString()
    });
});

// 获取连接状态接口
app.get('/status', (req, res) => {
    const clientsInfo = [];
    clients.forEach((client, clientId) => {
        clientsInfo.push({
            id: clientId,
            subscriptions: Array.from(client.subscriptions),
            readyState: client.ws.readyState
        });
    });
    
    res.json({
        total_clients: clients.size,
        clients: clientsInfo
    });
});

// 启动服务器
const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
    console.log(`奖池WebSocket服务器已启动，端口: ${PORT}`);
    console.log(`WebSocket地址: ws://localhost:${PORT}`);
    console.log(`HTTP推送地址: http://localhost:${PORT}/api/push-prize-pool`);
    console.log(`健康检查地址: http://localhost:${PORT}/health`);
    console.log(`状态查看地址: http://localhost:${PORT}/status`);
});

// 优雅关闭
process.on('SIGTERM', () => {
    console.log('收到SIGTERM信号，正在关闭服务器...');
    server.close(() => {
        console.log('服务器已关闭');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('收到SIGINT信号，正在关闭服务器...');
    server.close(() => {
        console.log('服务器已关闭');
        process.exit(0);
    });
});