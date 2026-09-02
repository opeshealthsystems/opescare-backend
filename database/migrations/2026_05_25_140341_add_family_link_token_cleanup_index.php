<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_links', function (Blueprint $table) {
            // Index speeds up cleanup query:
            // WHERE status='pending_invite' AND invite_expires_at < NOW()
            $table->index('invite_expires_at', 'family_links_invite_expires_at_index');

            // Index speeds up age-transition expiry check
            $table->index('age_transition_expires_at', 'family_links_age_transition_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('family_links', function (Blueprint $table) {
            $table->dropIndex('family_links_invite_expires_at_index');
            $table->dropIndex('family_links_age_transition_expires_at_index');
        });
    }
};
