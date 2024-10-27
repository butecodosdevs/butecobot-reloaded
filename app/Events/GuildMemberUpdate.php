<?php

namespace App\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Laracord\Events\Event;
use App\Models\Mongo\User as UserModel;

class GuildMemberUpdate extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::GUILD_MEMBER_UPDATE;

    /**
     * Handle the event.
     */
    public function handle(Member $member, Discord $discord, ?Member $oldMember)
    {
        $user = UserModel::where('guild_id', $member->guild_id)->where('discord_id', $member->user->id)->first();

        if (!$user) {
            $newAvatarUrlQuery = parse_url($member->user->avatar, PHP_URL_QUERY);
            $newAvatarFilename = basename($member->user->avatar, '?' . $newAvatarUrlQuery);
            $newAvatarContent = @file_get_contents($member->user->avatar);

            if ($newAvatarContent) {
                Storage::put("avatars/{$member->guild_id}/{$member->user->id}/{$newAvatarFilename}", $newAvatarContent);
            }

            UserModel::create([
                'guild_id' => $member->guild_id,
                'discord_id' => $member->user->id,
                'username' => $member->user->username,
                'global_name' => $member->user->global_name,
                'avatar' => $member->user->avatar,
                'avatar_filename' => $newAvatarFilename,
                'username_history' => [],
                'global_name_history' => [],
                'avatar_history' => [],
            ]);

            return;
        }

        if ($user->username !== $member->user->username) {
            $usernameHistoryLog = [
                'username' => $user->username,
                'created_at' => Date::now(),
            ];
            $user->username = $member->user->username;
            $user->username_history = array_merge($user->username_history ?? [], [$usernameHistoryLog]);
        }

        if ($user->global_name !== $member->user->global_name) {
            $globalNameHistoryLog = [
                'global_name' => $member->user->global_name,
                'created_at' => Date::now(),
            ];
            $user->global_name = $member->user->global_name;
            $user->global_name_history = array_merge($user->global_name_history ?? [], [$globalNameHistoryLog]);
        }

        if ($user->avatar !== $member->user->avatar) {
            $newAvatarUrlQuery = parse_url($member->user->avatar, PHP_URL_QUERY);
            $newAvatarFilename = basename($member->user->avatar, '?' . $newAvatarUrlQuery);
            $newAvatarContent = @file_get_contents($member->user->avatar);

            if ($newAvatarContent) {
                Storage::put("avatars/{$member->guild_id}/{$member->user->id}/{$newAvatarFilename}", $newAvatarContent);
            }

            $avatarHistoryLog = [
                'avatar' => $user->avatar_filename,
                'created_at' => Date::now(),
            ];
            $user->avatar = $member->user->avatar;
            $user->avatar_filename = $newAvatarFilename;
            $user->avatar_history = array_merge($user->avatar_history ?? [], [$avatarHistoryLog]);
        }

        $user->save();
    }
}
