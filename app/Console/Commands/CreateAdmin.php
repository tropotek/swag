<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'noteboard:create-admin {--force : Create another admin even if one exists}';

    protected $description = 'Create an administrator account';

    public function handle(): int
    {
        if (! $this->option('force') && User::where('is_admin', true)->exists()) {
            $this->error('An admin already exists. Re-run with --force to add another.');

            return self::FAILURE;
        }

        $input = [
            'name' => (string) $this->ask('Name'),
            'email' => mb_strtolower(trim((string) $this->ask('Email'))),
            'password' => (string) $this->secret('Password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::forceCreate([...$input, 'is_admin' => true]);

        $this->info("Admin {$input['email']} created.");

        return self::SUCCESS;
    }
}
