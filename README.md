# Badger PL

## Local Dev
0. Rename `.env.example` to `.env` and fill in values
1. Set `DB_CONNECTION=sqlite` in .evn
2. Comment out the rest of the `DB_*` variables in .env
3. Create `ADMIN_TOKEN=<some secret>` in .env (used for User Registration)
4. Set `UNISAT_API_TOKEN` and `BIS_API_TOKEN` and other API tokens in .env (used for external API calls)
5. Run `php artisan migrate` (`php artisan migrate:fresh` if needed)
6. Run `php artisan serve` in one tab
7. Run `npm run dev` in another tab


## Registering Users
1. Navigate to `/register`
2. Fill out the form with user information
3. Enter the Admin Token from .env
4. Send user their username and password


## Misc
```
# Reloading config after changes
php artisan config:clear
php artisan cache:clear
```


## Creating Admins
1. `php artisan tinker`
    > $user = App\Models\User::where('username', 'josh')->first();
    > $user->isAdmin = true;
    > $user->save();
    > exit
