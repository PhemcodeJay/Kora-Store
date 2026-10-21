#!/usr/bin/env python3
import sqlite3
conn = sqlite3.connect('C:/Users/Phemcode/Documents/Jobs/Clients/Kora Store/cybertrendhub.sqlite')
try:
    conn.execute('ALTER TABLE tbl_product ADD COLUMN is_dropshipping INTEGER DEFAULT 0')
    conn.commit()
    print('Column added successfully')
except Exception as e:
    print(f'Column may already exist or error: {e}')