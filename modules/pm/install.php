+----------+
|    PM    |
+----------+

1. id (int 11)
2. parent_id (int 11)
3. creator (varchar 255)
4. recipient (varchar 255)
5. message (longtext)
6. timestamp (unix_timestamp)
7. Subject (varchar 255)
 

+-----------+
|  PM_Info  |
+-----------+

1. post_id (int 11)
2. uid (int 11)
3. read (tiny int 1/0)
4. read_timestamp (unix_timestamp)
5. involve (text) - Will be a serialized (json) encoded array
6. last_updated (unix_timestamp)
7. deleted (tiny int 1/0)