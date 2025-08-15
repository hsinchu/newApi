#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
3F3D彩票期次生成脚本
基于ff3d的逻辑，生成3分钟一期的3f3d彩票期次数据
"""

import datetime

def generate_3f3d_sql():
    """
    生成3f3d彩票期次的SQL插入语句
    规则：
    - 1小时一期 (issue_time_interval = 3600秒)
    - closing_time 提前1分钟
    - draw_time_end 提前30秒
    - next_issue_start_time 为下一期的开始时间
    """
    
    sql_statements = []
    
    # 生成一天的期次 (24小时 / 1小时 = 24期)
    total_issues = 24  # 24期
    
    for issue_number in range(1, total_issues + 1):
        # 计算每期的开始时间（小时）
        start_hours = issue_number - 1
        start_minutes = start_hours * 60
        
        # 转换为时分秒
        hours = start_minutes // 60
        minutes = start_minutes % 60
        
        # 各个时间点
        draw_time_start = f"{hours:02d}:{minutes:02d}:00"
        
        # draw_time_end: 每期结束时间，提前30秒结束
        end_seconds = start_minutes * 60 + 3600 - 30  # 1小时-30秒
        end_hours = (end_seconds // 3600) % 24
        end_minutes = (end_seconds % 3600) // 60
        end_secs = end_seconds % 60
        draw_time_end = f"{end_hours:02d}:{end_minutes:02d}:{end_secs:02d}"
        
        # closing_time: 封盘时间，提前1分钟
        closing_seconds = start_minutes * 60 + 3600 - 60  # 1小时-1分钟
        closing_hours = (closing_seconds // 3600) % 24
        closing_mins = (closing_seconds % 3600) // 60
        closing_secs = closing_seconds % 60
        closing_time = f"{closing_hours:02d}:{closing_mins:02d}:{closing_secs:02d}"
        
        # next_issue_start_time: 下一期开始时间
        next_start_seconds = start_minutes * 60 + 3600  # 当前期开始时间 + 1小时
        next_start_hours = (next_start_seconds // 3600) % 24
        next_start_mins = (next_start_seconds % 3600) // 60
        next_start_secs = next_start_seconds % 60
        next_issue_start_time = f"{next_start_hours:02d}:{next_start_mins:02d}:{next_start_secs:02d}"
        
        # 生成SQL语句
        sql = f"""INSERT INTO fa_lottery_time 
(lottery_name, draw_date, draw_time_start, draw_time_end, closing_time, next_issue_start_time, current_issue_number, issue_time_interval, status) 
VALUES 
('3f3d', NULL, '{draw_time_start}', '{draw_time_end}', '{closing_time}', '{next_issue_start_time}', {issue_number}, 3600, 'active');"""
        
        sql_statements.append(sql)
    
    return sql_statements

def save_sql_to_file(sql_statements, filename='3f3d_insert.sql'):
    """
    将SQL语句保存到文件
    """
    with open(filename, 'w', encoding='utf-8') as f:
        f.write("-- 3F3D彩票期次数据插入脚本\n")
        f.write("-- 生成时间: {}\n".format(datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')))
        f.write("-- 规则: 1小时一期，closing_time提前1分钟，draw_time_end提前30秒\n\n")
        
        # 先删除已存在的3f3d数据
        f.write("-- 删除已存在的3f3d数据\n")
        f.write("DELETE FROM fa_lottery_time WHERE lottery_name = '3f3d';\n\n")
        
        # 插入新数据
        f.write("-- 插入3f3d期次数据\n")
        for sql in sql_statements:
            f.write(sql + "\n")
    
    print(f"SQL文件已生成: {filename}")
    print(f"共生成 {len(sql_statements)} 条记录")

def execute_sql_directly(sql_statements):
    """
    直接执行SQL语句到数据库
    注意：需要先安装 mysql-connector-python: pip install mysql-connector-python
    """
    print("直接执行数据库功能需要安装 mysql-connector-python 模块")
    print("请运行: pip install mysql-connector-python")
    print("然后取消注释下面的代码并配置数据库连接信息")
    
    # try:
    #     import mysql.connector
    #     from mysql.connector import Error
    #     
    #     # 数据库连接配置（请根据实际情况修改）
    #     connection = mysql.connector.connect(
    #         host='localhost',
    #         database='your_database_name',
    #         user='your_username',
    #         password='your_password'
    #     )
    #     
    #     if connection.is_connected():
    #         cursor = connection.cursor()
    #         
    #         # 先删除已存在的3f3d数据
    #         cursor.execute("DELETE FROM fa_lottery_time WHERE lottery_name = '3f3d'")
    #         print(f"删除了 {cursor.rowcount} 条已存在的3f3d记录")
    #         
    #         # 执行插入语句
    #         for sql in sql_statements:
    #             cursor.execute(sql)
    #         
    #         connection.commit()
    #         print(f"成功插入 {len(sql_statements)} 条3f3d记录")
    #         
    # except Error as e:
    #     print(f"数据库操作错误: {e}")
    # finally:
    #     if connection.is_connected():
    #         cursor.close()
    #         connection.close()

def main():
    """
    主函数
    """
    print("开始生成3F3D彩票期次数据...")
    
    # 生成SQL语句
    sql_statements = generate_3f3d_sql()
    
    # 保存到文件
    save_sql_to_file(sql_statements)
    
    # 显示前几条记录作为示例
    print("\n前5条记录示例:")
    for i, sql in enumerate(sql_statements[:5]):
        print(f"{i+1}. {sql}")
    
    print("\n脚本执行完成！")
    print("如需直接执行到数据库，请修改execute_sql_directly函数中的数据库连接信息，然后调用该函数。")

if __name__ == "__main__":
    main()