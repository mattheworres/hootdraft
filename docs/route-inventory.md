# Route inventory (old Silex router)

Generated from `old-api/config/_router.php`. URLs must stay identical in the Symfony port (step 4); this is the
porting checklist. Tick a row when the route is ported **and** its checks/firewall are wired.

Checks are the old `->before()` closures from `_middlewares.php`; they become `RouteCheck` services referenced
by a route's `_checks` default (see `src/EventListener/RouteCheckListener.php`). Firewall comes from
`_security.php` (the old `login` pattern is an unanchored match, so `/verifyToken` falls under it too).

| Done | Method | Path | Old controller::action | Access | Checks |
| --- | --- | --- | --- | --- | --- |
| [ ] | POST | `/login` | AuthenticationController::Login | public (login firewall) | - |
| [ ] | POST | `/register` | AuthenticationController::Register | public (login firewall) | - |
| [ ] | POST | `/verify` | AuthenticationController::VerifyAccount | public (login firewall) | - |
| [ ] | POST | `/lostPassword` | AuthenticationController::LostPassword | public (login firewall) | - |
| [ ] | GET | `/verifyToken` | AuthenticationController::VerifyResetPasswordToken | public (login firewall) | - |
| [ ] | POST | `/resetPassword` | AuthenticationController::ResetPassword | public (login firewall) | - |
| [ ] | GET | `/drafts` | DraftController::GetAll | public | - |
| [ ] | GET | `/drafts/{commish_id}` | DraftController::GetAllByCommish | public | - |
| [ ] | GET | `/draft/{id}` | DraftController::Get | public | - |
| [ ] | GET | `/draft/{draft_id}/stats` | DraftController::GetStats | public | draftViewable |
| [ ] | GET | `/commissioners/search` | CommishController::SearchPublicCommissioners | public | - |
| [ ] | GET | `/commissioners/{commish_id}` | CommishController::GetPublicCommissioner | public | - |
| [ ] | GET | `/draft/{draft_id}/managers` | ManagerController::GetAll | public | draftViewable |
| [ ] | GET | `/draft/{draft_id}/manager/{manager_id}/depth_chart` | ManagerController::GetManagerDepthChart | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/picks` | PickController::GetAll | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/picks/updated` | PickController::GetUpdated | public | draftViewable, draftInProgressOrCompleted |
| [ ] | PUT | `/draft/{draft_id}/pick/{pick_id}/depth_chart/{position_id}` | PickController::UpdateDepthChart | public | draftViewable, draftInProgressOrCompletedTenMinutes |
| [ ] | GET | `/draft/{draft_id}/picks/last` | PickController::GetLast | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/picks/next` | PickController::GetNext | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/manager/{manager_id}/picks/all` | PickController::GetAllManagerPicks | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/manager/{manager_id}/picks/selected` | PickController::GetSelectedManagerPicks | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/round/{draft_round}/picks/all` | PickController::GetAllRoundPicks | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/round/{draft_round}/picks/selected` | PickController::GetSelectedRoundPicks | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/picks/search` | PickController::SearchPicks | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/trades` | TradeController::GetAll | public | draftViewable, draftInProgressOrCompleted |
| [ ] | GET | `/draft/{draft_id}/timer/remaining` | RoundTimeController::GetTimeRemaining | public | draftViewable, draftInProgress |
| [ ] | GET | `/style` | IndexController::Style | public | - |
| [ ] | GET | `/draftOptions` | IndexController::DraftOptions | public | - |
| [ ] | GET | `/admin/drafts` | Admin\DraftStatsController::GetDrafts | admin (ROLE_ADMIN) | - |
| [ ] | POST | `/admin/draft/{draft_id}/stats` | Admin\DraftStatsController::Create | admin (ROLE_ADMIN) | - |
| [ ] | GET | `/admin/sports` | Admin\ProPlayerController::GetSports | admin (ROLE_ADMIN) | - |
| [ ] | POST | `/admin/proplayers` | Admin\ProPlayerController::Upload | admin (ROLE_ADMIN) | - |
| [ ] | GET | `/admin/users` | Admin\UserController::Get | admin (ROLE_ADMIN) | - |
| [ ] | PUT | `/admin/user/{user_id}` | Admin\UserController::Update | admin (ROLE_ADMIN) | - |
| [ ] | DELETE | `/admin/user/{user_id}` | Admin\UserController::Delete | admin (ROLE_ADMIN) | - |
| [ ] | GET | `/commish` | Commish\IndexController::Index | commish (ROLE_COMMISH) | - |
| [ ] | POST | `/commish/user/invite` | Commish\UserProfileController::InviteNewCommissioner | commish (ROLE_COMMISH) | - |
| [ ] | GET | `/commish/profile` | Commish\UserProfileController::Get | commish (ROLE_COMMISH) | - |
| [ ] | PUT | `/commish/profile` | Commish\UserProfileController::Put | commish (ROLE_COMMISH) | - |
| [ ] | GET | `/commish/draft/create` | Commish\DraftController::GetCreate | commish (ROLE_COMMISH) | - |
| [ ] | GET | `/commish/depthchartposition/positions` | Commish\DepthChartPositionController::GetPositions | commish (ROLE_COMMISH) | - |
| [ ] | GET | `/commish/draft/{draft_id}` | Commish\DraftController::Get | commish (ROLE_COMMISH) | commishEditableDraft |
| [ ] | GET | `/commish/draft/{draft_id}/timers` | Commish\DraftController::GetTimers | commish (ROLE_COMMISH) | commishEditableDraft |
| [ ] | POST | `/commish/draft` | Commish\DraftController::Create | commish (ROLE_COMMISH) | - |
| [ ] | PUT | `/commish/draft/{draft_id}` | Commish\DraftController::Update | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | PUT | `/commish/draft/{draft_id}/status` | Commish\DraftController::UpdateStatus | commish (ROLE_COMMISH) | commishEditableDraft |
| [ ] | DELETE | `/commish/draft/{draft_id}` | Commish\DraftController::Delete | commish (ROLE_COMMISH) | commishEditableDraft |
| [ ] | POST | `/commish/draft/{draft_id}/timers` | Commish\DraftController::SetTimers | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | GET | `/commish/draft/{draft_id}/managers` | Commish\ManagerController::Get | commish (ROLE_COMMISH) | commishEditableDraft |
| [ ] | POST | `/commish/draft/{draft_id}/manager` | Commish\ManagerController::Create | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | POST | `/commish/draft/{draft_id}/managers` | Commish\ManagerController::CreateMany | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | PUT | `/commish/draft/{draft_id}/managers/reorder` | Commish\ManagerController::Reorder | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | PUT | `/commish/draft/{draft_id}/manager/{manager_id}` | Commish\ManagerController::Update | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | DELETE | `/commish/draft/{draft_id}/manager/{manager_id}` | Commish\ManagerController::Delete | commish (ROLE_COMMISH) | commishEditableDraft, draftSettingUp |
| [ ] | GET | `/commish/proplayers/search` | Commish\ProPlayerController::Search | commish (ROLE_COMMISH) | - |
| [ ] | GET | `/commish/draft/{draft_id}/manager/{manager_id}/assets` | Commish\TradeController::GetAssets | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | POST | `/commish/draft/{draft_id}/trade` | Commish\TradeController::Create | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | GET | `/commish/draft/{draft_id}/pick/current` | Commish\PickController::GetCurrent | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | POST | `/commish/draft/{draft_id}/pick/{player_id}` | Commish\PickController::Add | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | GET | `/commish/draft/{draft_id}/picks/lastFive` | Commish\PickController::GetLast5 | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | GET | `/commish/draft/{draft_id}/round/{draft_round}/picks` | Commish\PickController::GetByRound | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | PUT | `/commish/draft/{draft_id}/pick/{player_id}` | Commish\PickController::Update | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |
| [ ] | GET | `/commish/draft/{draft_id}/picks/alreadyDrafted` | Commish\PickController::AlreadyDrafted | commish (ROLE_COMMISH) | commishEditableDraft, draftInProgress |

63 routes.
