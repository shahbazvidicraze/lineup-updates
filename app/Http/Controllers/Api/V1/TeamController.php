<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Team;
use App\Models\User; // Import User
use App\Models\Organization; // Import Organization
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response ;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // For policies
use Carbon\Carbon; // For is_editable_until

class TeamController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests; // <-- INCLUDE TRAIT

    public function index(Request $request)
    {
        $user = $request->user();
        $teams = $user->teams()
            ->with('organization') // Eager load organization if needed
            ->orderBy('id', 'desc')
            ->get();
        // Return paginated data using the trait
        // $teams = $query->paginate($request->input('per_page', 15));
        // return $this->successResponse($teams, 'Teams retrieved successfully.');

        // Get the user's available team activation slots count
        // This uses the accessor `getAvailableTeamSlotsCountAttribute` from the User model
        $availableSlotsCount = $user->available_team_slots_count;

        Log::info("Found " . $teams->count() . " teams and {$availableSlotsCount} available slots for User ID: {$user->id}");

        // Prepare data for the response
        $responseData = [
            'teams' => $teams,
            'available_team_slots_count' => $availableSlotsCount,
        ];

        if ($teams->isNotEmpty()) {
            return $this->successResponse($responseData, 'Teams retrieved successfully.');
        } else {
            return $this->successResponse([], 'No teams created yet.'); // Return empty array
        }
    }

    /**
     * Store a newly created team.
     * Supports linking to an existing active org via code, or creating an unlinked team
     * that can be activated directly later.
     * Implements "placeholder team" creation upon successful org code validation or pre-activation.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Initial validation for core fields or just the org code if that's the first step
        $validator = Validator::make($request->all(), [
            'organization_code' => 'nullable|string|exists:organizations,organization_code',
            // If placeholder: name might be optional initially
            'name' => 'required|string|max:255',
            'sport_type' => 'required|string', Rule::in(['baseball', 'softball']),
            'team_type' => 'required|string', Rule::in(['travel', 'recreation', 'school']),
            'age_group' => 'required|string|max:50',
            'season' => 'nullable|string|max:50',
            'year' => 'nullable|integer|digits:4|min:1901',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $validatedData = $validator->validated();
        $organizationIdToStore = null;

        if (!empty($validatedData['organization_code'])) {
            $organization = Organization::where('organization_code', strtoupper($validatedData['organization_code']))->first();
            if (!$organization) {
                return $this->errorResponse('Invalid organization code provided.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if (!$organization->hasActiveSubscription()) {
                return $this->forbiddenResponse("Organization '{$organization->name}' does not have an active subscription.");
            }
            if (!$organization->canCreateMoreTeams()) {
                return $this->forbiddenResponse("Organization '{$organization->name}' has reached its team creation limit for this period.");
            }
            $organizationIdToStore = $organization->id;
        }else {
            // Path A: User wants to use one of their direct activation slots
            $availableSlot = $user->teamActivationSlots()
                ->where('status', 'available')
                ->where('slot_expires_at', '>', now())
                ->orderBy('created_at', 'asc') // Use oldest available slot
                ->first();
            if (!$availableSlot) {
                return $this->errorResponse('You do not have an available team activation slot. Please purchase one or use a promo code.', Response::HTTP_FORBIDDEN);
            }
            $teamToActivateUsingSlot = $availableSlot;
        }

        try {
            $teamData = [
                'user_id' => $user->id,
                'organization_id' => $organizationIdToStore, // Null if Path A
                'name' => $validatedData['name'],
                'sport_type' => $validatedData['sport_type'] ?? 'baseball', // Default or make required
                'team_type' => $validatedData['team_type'] ?? 'recreation',
                'age_group' => $validatedData['age_group'] ?? 'N/A',
                'season' => $validatedData['season'] ?? null,
                'year' => $validatedData['year'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'state' => $validatedData['state'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'is_setup_complete' => true, // Assume full details provided now
                'is_editable_until' => Carbon::now()->addYear(),
            ];


            if (isset($teamToActivateUsingSlot) && $teamToActivateUsingSlot) { // Path A
                $teamData['direct_activation_status'] = 'active';
                $teamData['direct_activation_expires_at'] = $teamToActivateUsingSlot->slot_expires_at;
            } else { // Path B
                $teamData['direct_activation_status'] = 'inactive'; // Relies on Org for premium
                $teamData['direct_activation_expires_at'] = null;
            }

            $team = Team::create($teamData);



            if (isset($teamToActivateUsingSlot) && $teamToActivateUsingSlot ) {
                $teamToActivateUsingSlot->status = 'used';
                $teamToActivateUsingSlot->team_id = $team->id;
                $teamToActivateUsingSlot->save();
            } elseif ($organizationIdToStore) {
                $organization->increment('teams_created_this_period');
            }

            if ($team->organization_id) $team->load('organization:id,name');
            $message = "Team '{$team->name}' created successfully.";
            if ($team->direct_activation_status === 'active') $message .= " It is directly activated until " . $team->direct_activation_expires_at->toFormattedDayDateString() . ".";
            elseif ($team->organization_id) $message .= " It is linked to organization '{$team->organization->name}'.";

            return $this->successResponse($team, $message, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Team creation failed for User ID {$user->id}: " . $e->getMessage(), ['exception' => $e]);
            return $this->errorResponse('Failed to create team.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeOld(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sport_type' => ['required', Rule::in(['baseball', 'softball'])],
            'team_type' => ['required', Rule::in(['travel', 'recreation', 'school'])],
            'age_group' => 'required|string|max:50',
            'season' => 'nullable|string|max:50',
            'year' => 'nullable|integer|digits:4',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'organization_code' => [ // User MUST provide code of an active org
                'required', 'string', 'max:50',
                Rule::exists('organizations', 'organization_code')
            ],
        ]);
        if ($validator->fails()) return $this->validationErrorResponse($validator);

        $validatedData = $validator->validated();
        $organization = Organization::where('organization_code', strtoupper($validatedData['organization_code']))->first(); // firstOrFail() not needed due to exists rule

        if (!$organization) { // Should be caught by 'exists' but defensive check
            return $this->errorResponse('Invalid organization code provided.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$organization->hasActiveSubscription()) {
            return $this->forbiddenResponse('The specified organization ('.$organization->name.') does not have an active subscription. Please have the organization admin renew it.');
        }

        $validatedData['organization_id'] = $organization->id;
        unset($validatedData['organization_code']);

        $team = $user->teams()->create($validatedData); // User still "owns" the team for management
        $team->load('organization:id,name,organization_code');
        return $this->successResponse($team, 'Team created successfully under organization: ' . $organization->name, Response::HTTP_CREATED);
    }

    public function show(Request $request, Team $team)
    {
        if ($request->user()->id !== $team->user_id) return $this->forbiddenResponse('You do not own this team.');
        $team->load(['organization:id,name,organization_code', 'games', 'players' => function($q){
            $q->select(['id', 'team_id', 'first_name', 'last_name', 'jersey_number', 'email']); // Select specific player columns
        }]);
        return $this->successResponse($team);
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team); // Policy checks ownership & if team is editable

        if ($request->user()->id !== $team->user_id) return $this->forbiddenResponse('You do not own this team.');
        $validator = Validator::make($request->all(), [ /* same as store but 'sometimes|required' */
            'name' => 'sometimes|required|string|max:255',
            'sport_type' => ['sometimes','required', Rule::in(['baseball', 'softball'])],
            'team_type' => ['sometimes','required', Rule::in(['travel', 'recreation', 'school'])],
            'age_group' => 'sometimes|required|string|max:50',
            'season' => 'nullable|string|max:50', 'year' => 'nullable|integer|digits:4',
            'city' => 'nullable|string|max:100', 'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100', 'organization_id' => 'nullable|exists:organizations,id',
            'is_setup_complete' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) return $this->validationErrorResponse($validator);

        $validatedData = $validator->validated();
        // Ensure setup complete is only set to true, not false by accident
        if (isset($validatedData['is_setup_complete']) && !$validatedData['is_setup_complete'] && $team->is_setup_complete) {
            // Prevent marking a complete team as incomplete unless specific logic allows
            unset($validatedData['is_setup_complete']);
        }


        $team->update($validatedData);
        $team->load('organization:id,name,organization_code');
        return $this->successResponse($team, 'Team updated successfully.');
    }

    public function destroy(Request $request, Team $team)
    {
        if ($request->user()->id !== $team->user_id) return $this->forbiddenResponse('You do not own this team.');
        $team->delete();
        return $this->successResponse(null, 'Team deleted successfully.', Response::HTTP_OK, false);
    }

    public function listPlayers(Request $request, Team $team)
    {
        if ($request->user()->id !== $team->user_id && !$request->user('api_admin')) {
            return $this->forbiddenResponse('Cannot access this team\'s players.');
        }
        $players = $team->players()->select(['id','team_id','first_name','last_name','jersey_number','email', 'phone'])->orderBy('last_name')->orderBy('first_name')->get();
        // $players = $team->players()->select([...])->orderBy(...)->paginate(50);
        // return $this->successResponse($players, 'Players retrieved successfully.');

        if ($players->isNotEmpty()) {
            return $this->successResponse($players, 'Players retrieved successfully.');
        }
        return $this->successResponse([], 'No players found for this team.');
    }
}
