<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Settings;
use App\Models\UserTeamActivationSlot;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'organization_id', 'name', 'sport_type', 'team_type', 'age_group',
        'city', 'state', 'country',
        'direct_activation_status', 'direct_activation_expires_at',
        'is_editable_until', 'is_setup_complete'
    ];

    protected $casts = [
        'year' => 'integer',
        'direct_activation_expires_at' => 'datetime',
        'is_editable_until' => 'datetime',
        'is_setup_complete' => 'boolean'
    ];

    // --- Relationships ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function directPayments() { return $this->morphMany(Payment::class, 'payable'); }
    public function directPromoRedemptions() { return $this->morphMany(PromoCodeRedemption::class, 'redeemable'); }

    public function isEditable(): bool {
        return !$this->is_editable_until || $this->is_editable_until->isFuture();
    }

    // This method determines if PDF and other premium features are available
    public function hasPremiumAccess(): bool {
        // Path A: Direct team activation
        if ($this->direct_activation_status === 'active' &&
            $this->direct_activation_expires_at &&
            $this->direct_activation_expires_at->isFuture()) {
            return true;
        }
        // Path B: Inherited from Organization
        if ($this->organization_id && $this->organization) {
            $this->loadMissing('organization'); // Ensure org is loaded
            return $this->organization->hasActiveSubscription();
        }
        return false;
    }

    /**
     * Activates this team directly using a pre-paid/promo slot.
     * Consumes a UserTeamActivationSlot.
     */
    public function activateWithSlot(UserTeamActivationSlot $slot): bool
    {
        if ($slot->user_id !== $this->user_id || $slot->status !== 'available' || $slot->slot_expires_at->isPast()) {
            return false; // Slot not valid for this team/user or expired
        }

        $this->direct_activation_status = 'active';
        $this->direct_activation_expires_at = $slot->slot_expires_at;
        $this->is_setup_complete = true; // Assuming details are provided now or will be
        if (!$this->is_editable_until) { // Set initial editability window if not already set
            $this->is_editable_until = Carbon::now()->addYear();
        }
        $this->save();

        // Mark the slot as used and link it to this team
        $slot->status = 'used';
        $slot->team_id = $this->id;
        $slot->save();

        return true;
    }

    public function activateDirectly(Carbon $expiresAt): void {
        $this->direct_activation_status = 'active';
        $this->direct_activation_expires_at = $expiresAt;
        if (!$this->is_editable_until) { // Set initial editability window
            $this->is_editable_until = Carbon::now()->addYear();
        }
        $this->save();
    }
}
