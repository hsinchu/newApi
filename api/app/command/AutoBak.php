<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;
use think\facade\Config;
use app\common\library\Email;
use Exception;

/**
 * php think autobak -e your-email@example.com
 */

class AutoBak extends Command
{
    protected function configure()
    {
        // 设置内存限制，防止段错误
        ini_set('memory_limit', '1024M');
        
        $this->setName('autobak')
            ->setDescription('自动备份数据库并发送邮件通知')
            ->addOption('email', 'e', \think\console\input\Option::VALUE_OPTIONAL, '接收备份文件的邮箱地址')
            ->addOption('force', 'f', \think\console\input\Option::VALUE_NONE, '强制执行，忽略锁定检查');
    }

    protected function execute(Input $input, Output $output)
    {
        $email = $input->getOption('email');
        $force = $input->getOption('force');
        
        $output->writeln('开始执行数据库备份任务');
        
        // 防重复执行锁
        $lockKey = 'autobak_lock';
        
        if (!$force && Cache::get($lockKey)) {
            $output->writeln('数据库备份任务正在执行中，跳过本次执行');
            return;
        }
        
        // 设置锁，有效期为30分钟
        Cache::set($lockKey, time(), 1800);
        
        try {
            // 执行数据库备份
            $backupFile = $this->backupDatabase($output);
            
            if ($backupFile && file_exists($backupFile)) {
                $output->writeln("数据库备份成功: {$backupFile}");
                
                // 发送邮件通知
                if ($email) {
                    $this->sendBackupEmail($email, $backupFile, $output);
                } else {
                    // 从系统配置获取默认邮箱
                    $defaultEmail = get_sys_config('backup_email', 'system');
                    if ($defaultEmail) {
                        $this->sendBackupEmail($defaultEmail, $backupFile, $output);
                    } else {
                        $output->writeln('未指定邮箱地址，跳过邮件发送');
                    }
                }
                
                // 邮件发送完成后删除备份文件
                if (file_exists($backupFile)) {
                    if (unlink($backupFile)) {
                        $output->writeln('备份文件已删除: ' . basename($backupFile));
                        Log::info('备份文件已删除: ' . $backupFile);
                    } else {
                        $output->writeln('删除备份文件失败: ' . basename($backupFile));
                        Log::error('删除备份文件失败: ' . $backupFile);
                    }
                }
                
                // 清理旧备份文件（保留最近7天）
                $this->cleanOldBackups($output);
                
                // 备份成功后清空数据库所有数据
                $this->clearDatabase($output);
                
            } else {
                $output->writeln('数据库备份失败');
                Log::error('数据库备份失败');
            }
            
        } catch (Exception $e) {
                $output->writeln('备份任务执行失败: ' . $e->getMessage());
                Log::error('备份任务执行失败: ' . $e->getMessage());
            } finally {
                // 释放锁
                Cache::delete($lockKey);
            }
            
            $output->writeln('数据库备份任务执行完成');
    }

    /**
     * 备份数据库
     * @param Output $output
     * @return string|false
     */
    private function backupDatabase(Output $output)
    {
        try {
            // 获取数据库配置
            $dbConfig = Config::get('database.connections.mysql');
            $database = $dbConfig['database'];
            $prefix = $dbConfig['prefix'];
            
            // 创建备份目录
            $backupDir = root_path() . 'backup';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // 生成备份文件名
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $backupDir . DIRECTORY_SEPARATOR . "backup_{$database}_{$timestamp}.sql";
            
            $output->writeln("开始备份数据库: {$database}");
            
            // 开始生成SQL备份（标准mysqldump格式）
            $sql = "-- MySQL dump 10.13  Distrib 8.0.24, for Windows (x86_64)\n";
            $sql .= "--\n";
            $sql .= "-- Host: localhost    Database: {$database}\n";
            $sql .= "-- ------------------------------------------------------\n";
            $sql .= "-- Server version\t8.0.24\n\n";
            
            $sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
            $sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
            $sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
            $sql .= "/*!50503 SET NAMES utf8mb4 */;\n";
            $sql .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
            $sql .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
            $sql .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
            $sql .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
            $sql .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
            $sql .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
            
            // 获取所有表
            $tables = Db::query('SHOW TABLES');
            $tableCount = count($tables);
            $output->writeln("发现 {$tableCount} 个表，开始备份...");
            
            foreach ($tables as $index => $table) {
                $tableName = array_values($table)[0];
                $output->writeln("备份表: {$tableName} (" . ($index + 1) . "/{$tableCount})");
                
                // 获取表结构
                $createTable = Db::query("SHOW CREATE TABLE `{$tableName}`");
                $sql .= "--\n";
                $sql .= "-- Table structure for table `{$tableName}`\n";
                $sql .= "--\n\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
                $sql .= "/*!50503 SET character_set_client = utf8mb4 */;\n";
                
                // 处理CREATE TABLE语句，简化数据类型定义
                $createTableSql = $createTable[0]['Create Table'];
                // 简化数据类型定义以匹配标准mysqldump格式
                $createTableSql = preg_replace('/\bint\(\d+\)\s+unsigned\b/', 'int unsigned', $createTableSql);
                $createTableSql = preg_replace('/\btinyint\(\d+\)\s+unsigned\b/', 'tinyint unsigned', $createTableSql);
                $createTableSql = preg_replace('/\bbigint\(\d+\)\s+unsigned\b/', 'bigint unsigned', $createTableSql);
                
                $sql .= $createTableSql . ";\n";
                $sql .= "/*!40101 SET character_set_client = @saved_cs_client */;\n\n";
                
                // 获取表数据
                $rows = Db::table($tableName)->select();
                if (!empty($rows)) {
                    $sql .= "--\n";
                    $sql .= "-- Dumping data for table `{$tableName}`\n";
                    $sql .= "--\n\n";
                    $sql .= "LOCK TABLES `{$tableName}` WRITE;\n";
                    $sql .= "/*!40000 ALTER TABLE `{$tableName}` DISABLE KEYS */;\n";
                    
                    $values = [];
                    // 获取表结构信息以确定字段类型
                    $columns = Db::query("SHOW COLUMNS FROM `{$tableName}`");
                    $columnTypes = [];
                    foreach ($columns as $column) {
                        $columnTypes[$column['Field']] = $column['Type'];
                    }
                    
                    foreach ($rows as $row) {
                        $rowValues = [];
                        $fieldIndex = 0;
                        foreach ($row as $field => $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $columnType = isset($columnTypes[$field]) ? $columnTypes[$field] : '';
                                
                                // 根据字段类型和值的特征来决定是否加引号
                                if (is_numeric($value) && 
                                    (strpos($columnType, 'int') !== false || 
                                     strpos($columnType, 'decimal') !== false || 
                                     strpos($columnType, 'float') !== false || 
                                     strpos($columnType, 'double') !== false) &&
                                    !preg_match('/^0\d+$/', $value)) { // 排除以0开头的数字字符串
                                    // 纯数字类型字段不加引号
                                    $rowValues[] = $value;
                                } else {
                                    // 字符串类型或特殊数字（如手机号）加引号并转义
                                    $rowValues[] = "'" . addslashes($value) . "'";
                                }
                            }
                            $fieldIndex++;
                        }
                        $values[] = '(' . implode(',', $rowValues) . ')';
                    }
                    
                    // 只有当有数据时才生成INSERT语句
                    if (!empty($values)) {
                        $sql .= "INSERT INTO `{$tableName}` VALUES " . implode(",\n", $values) . ";\n";
                    }
                    $sql .= "/*!40000 ALTER TABLE `{$tableName}` ENABLE KEYS */;\n";
                    $sql .= "UNLOCK TABLES;\n\n";
                } else {
                    // 表为空时，添加注释说明
                    $sql .= "--\n";
                    $sql .= "-- Dumping data for table `{$tableName}`\n";
                    $sql .= "--\n\n";
                    $sql .= "LOCK TABLES `{$tableName}` WRITE;\n";
                    $sql .= "/*!40000 ALTER TABLE `{$tableName}` DISABLE KEYS */;\n";
                    $sql .= "/*!40000 ALTER TABLE `{$tableName}` ENABLE KEYS */;\n";
                    $sql .= "UNLOCK TABLES;\n\n";
                }
            }
            
            // 恢复MySQL设置
            $sql .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
            $sql .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
            $sql .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
            $sql .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
            $sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
            $sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
            $sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
            $sql .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n\n";
            $sql .= "-- Dump completed on " . date('Y-m-d') . "  " . date('H:i:s') . "\n";
            $sql .= "-- Generated by AutoBak Command\n";
            
            // 写入文件
            $output->writeln('写入备份文件...');
            if (file_put_contents($backupFile, $sql) !== false) {
                $fileSize = $this->formatBytes(filesize($backupFile));
                $output->writeln("备份完成，文件大小: {$fileSize}");
                Log::info("数据库备份成功: {$backupFile}, 大小: {$fileSize}");
                return $backupFile;
            } else {
                $output->writeln('写入备份文件失败');
                Log::error('写入备份文件失败');
                return false;
            }
            
        } catch (Exception $e) {
            $output->writeln('备份过程中发生异常: ' . $e->getMessage());
            Log::error('数据库备份异常: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送备份邮件
     * @param string $email
     * @param string $backupFile
     * @param Output $output
     */
    private function sendBackupEmail($email, $backupFile, Output $output)
    {
        try {
            $output->writeln("发送备份邮件到: {$email}");
            
            $mail = new Email();
            
            if (!$mail->configured) {
                $output->writeln('邮件服务未配置，跳过邮件发送');
                return;
            }
            
            // 设置邮件内容
            $subject = '数据库备份通知 - ' . date('Y-m-d H:i:s');
            $fileName = basename($backupFile);
            $fileSize = $this->formatBytes(filesize($backupFile));
            $dbName = Config::get('database.connections.mysql.database');
            
            $content = "
            <h3>数据库备份完成通知</h3>
            <p><strong>备份时间:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>数据库名:</strong> {$dbName}</p>
            <p><strong>备份文件:</strong> {$fileName}</p>
            <p><strong>文件大小:</strong> {$fileSize}</p>
            <p><strong>备份状态:</strong> <span style='color: green;'>成功</span></p>
            <hr>
            <p style='color: #666; font-size: 12px;'>此邮件由系统自动发送，请勿回复。</p>
            ";
            
            // 配置邮件
            $mail->isSMTP();
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->setSubject($subject);
            $mail->Body = $content;
            $mail->AltBody = strip_tags($content);
            
            // 添加附件（如果文件不太大）
            $maxAttachmentSize = 10 * 1024 * 1024; // 10MB
            if (filesize($backupFile) <= $maxAttachmentSize) {
                $mail->addAttachment($backupFile, $fileName);
                $output->writeln('备份文件已添加为邮件附件');
            } else {
                $output->writeln('备份文件过大，不添加为附件');
            }
            
            // 发送邮件
            $mail->send();
            $output->writeln('备份邮件发送成功');
            Log::info("备份邮件发送成功到: {$email}");
            
        } catch (Exception $e) {
            $output->writeln('邮件发送失败: ' . $e->getMessage());
            Log::error('备份邮件发送失败: ' . $e->getMessage());
        }
    }

    /**
     * 清理旧备份文件
     * @param Output $output
     */
    private function cleanOldBackups(Output $output)
    {
        try {
            $backupDir = root_path() . 'backup';
            if (!is_dir($backupDir)) {
                return;
            }
            
            $output->writeln('清理旧备份文件...');
            
            // 获取所有备份文件
            $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql');
            
            if (empty($files)) {
                return;
            }
            
            // 按修改时间排序
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // 保留最近7个备份文件
            $keepCount = 7;
            $deletedCount = 0;
            
            for ($i = $keepCount; $i < count($files); $i++) {
                if (unlink($files[$i])) {
                    $deletedCount++;
                    $output->writeln('删除旧备份: ' . basename($files[$i]));
                }
            }
            
            if ($deletedCount > 0) {
                $output->writeln("清理完成，删除了 {$deletedCount} 个旧备份文件");
                Log::info("清理旧备份文件完成，删除了 {$deletedCount} 个文件");
            } else {
                $output->writeln('无需清理旧备份文件');
            }
            
        } catch (Exception $e) {
            $output->writeln('清理旧备份文件失败: ' . $e->getMessage());
            Log::error('清理旧备份文件失败: ' . $e->getMessage());
        }
    }

    /**
     * 清空数据库所有数据
     * @param Output $output
     */
    private function clearDatabase(Output $output)
    {
        try {
            $output->writeln('开始清空数据库所有数据...');
            
            // 禁用外键检查
            Db::execute('SET FOREIGN_KEY_CHECKS = 0');
            
            // 获取所有表名
            $tables = Db::query('SHOW TABLES');
            $tableCount = 0;
            
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                
                // 清空表数据（保留表结构）
                Db::execute("TRUNCATE TABLE `{$tableName}`");
                $tableCount++;
                
                $output->writeln("已清空表: {$tableName}");
            }
            
            // 重新启用外键检查
            Db::execute('SET FOREIGN_KEY_CHECKS = 1');
            
            $output->writeln("数据库清空完成，共清空 {$tableCount} 个表的数据");
            Log::info("数据库清空完成，共清空 {$tableCount} 个表的数据");
            
        } catch (Exception $e) {
            $output->writeln('清空数据库失败: ' . $e->getMessage());
            Log::error('清空数据库失败: ' . $e->getMessage());
            
            // 确保重新启用外键检查
            try {
                Db::execute('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Exception $ex) {
                // 忽略错误
            }
        }
    }

    /**
     * 格式化文件大小
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}