<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE VIRTUAL TABLE pages_fts USING fts5(
                title, body_markdown,
                content='pages', content_rowid='id',
                tokenize='porter unicode61'
            );

            CREATE TRIGGER pages_fts_insert AFTER INSERT ON pages BEGIN
                INSERT INTO pages_fts(rowid, title, body_markdown)
                VALUES (new.id, new.title, new.body_markdown);
            END;

            CREATE TRIGGER pages_fts_delete AFTER DELETE ON pages BEGIN
                INSERT INTO pages_fts(pages_fts, rowid, title, body_markdown)
                VALUES ('delete', old.id, old.title, old.body_markdown);
            END;

            CREATE TRIGGER pages_fts_update AFTER UPDATE OF title, body_markdown ON pages BEGIN
                INSERT INTO pages_fts(pages_fts, rowid, title, body_markdown)
                VALUES ('delete', old.id, old.title, old.body_markdown);
                INSERT INTO pages_fts(rowid, title, body_markdown)
                VALUES (new.id, new.title, new.body_markdown);
            END;

            INSERT INTO pages_fts(pages_fts) VALUES ('rebuild');
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS pages_fts_insert;
            DROP TRIGGER IF EXISTS pages_fts_delete;
            DROP TRIGGER IF EXISTS pages_fts_update;
            DROP TABLE IF EXISTS pages_fts;
        SQL);
    }
};
