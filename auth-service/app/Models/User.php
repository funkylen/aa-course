<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Producer;
use App\SchemaRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'public_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => Role::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (User $model) {
            $event = [
                'event_id' => (string) Str::uuid(),
                'event_version' => 1,
                'event_name' => 'UserCreated',
                'data' => [
                    'public_id' => $model->public_id,
                    'email' => $model->email,
                    'name' => $model->name,
                    'role' => $model->role,
                ],
            ];

            if (!SchemaRegistry::validateEvent($event, 'users.created', $event['event_version'])) {
                throw new \Exception('Event Schema Validation Failed');
            }

            Producer::call($event, 'users-stream');
        });
    }
}
