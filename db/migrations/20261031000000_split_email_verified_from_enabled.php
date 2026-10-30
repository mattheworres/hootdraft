<?php

use Phinx\Migration\AbstractMigration;

class SplitEmailVerifiedFromEnabled extends AbstractMigration
{
    public function up()
    {
        $this->table('users')
            ->addColumn('emailVerified', 'boolean', ['limit' => 1, 'default' => 0, 'null' => false, 'after' => 'enabled'])
            ->addColumn('emailVerificationKey', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('emailVerificationKeyCreated', 'datetime', ['null' => true, 'default' => null])
            ->update();

        $this->table('users')
            ->renameColumn('verificationKey', 'resetKey')
            ->renameColumn('verificationKeyCreated', 'resetKeyCreated')
            ->update();

        // Until now `enabled` meant "email verified" too. Everybody starts Email Verified
        $this->execute('UPDATE users SET emailVerified = 1');

        // ... except a disabled user with a pending key. That was Email Unverified: the account stays blocked until
        // the emailed link is followed, and the key moves to the email columns. (An Admin-disabled user who happens to
        // have a pending reset key is misread as Email Unverified. We accept this: they stay blocked indefinitely.)
        $this->execute('UPDATE users
            SET emailVerified = 0, enabled = 1,
              emailVerificationKey = resetKey, emailVerificationKeyCreated = resetKeyCreated,
              resetKey = NULL, resetKeyCreated = NULL
            WHERE enabled = 0 AND resetKey IS NOT NULL');
    }

    public function down()
    {
        // An Email Unverified user goes back to being disabled, holding the email verification key where the old code looks for it
        $this->execute('UPDATE users
            SET enabled = 0, resetKey = emailVerificationKey, resetKeyCreated = emailVerificationKeyCreated
            WHERE emailVerified = 0');

        $this->table('users')
            ->renameColumn('resetKey', 'verificationKey')
            ->renameColumn('resetKeyCreated', 'verificationKeyCreated')
            ->update();

        $this->table('users')
            ->removeColumn('emailVerified')
            ->removeColumn('emailVerificationKey')
            ->removeColumn('emailVerificationKeyCreated')
            ->update();
    }
}
