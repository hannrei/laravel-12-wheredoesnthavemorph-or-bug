# [Eloquent] whereDoesntHaveMorph('relation', '*') generates ungrouped OR

## Laravel Version

12.39.0

## PHP Version

8.4

## Database Driver & Version

postgreSQL 18

## Description

When calling `whereDoesntHaveMorph('relation', '*')` on a nullable morphTo relationship, Eloquent adds an OR ... IS NULL
to include “no morph assigned” rows.
Currently, that OR is appended outside the grouped relation clause.
This produces ungrouped SQL, so prior or subsequent where conditions are not consistently applied depending on ordering,
due to SQL operator precedence.
The result set can be wider than intended.

Imagine a model `Notification` with a nullable morphTo notifiable to `Video` and `Post`, plus a boolean column
`is_urgent`.

Query 1:

```
App\Models\Notification::where('is_urgent', false)
    ->whereDoesntHaveMorph('notifiable', '*')
    ->toRawSql();
```

Produces SQL similar to:

```
select * from "notifications" 
where 
"is_urgent" = 0 
and (
    ("notifications"."notifiable_type" = 'App\Models\Video' and not exists (select * from "videos" where "notifications"."notifiable_id" = "videos"."id")) 
    or ("notifications"."notifiable_type" = 'App\Models\Post' and not exists (select * from "posts" where "notifications"."notifiable_id" = "posts"."id"))
) 
or "notifications"."notifiable_type" is null
```

Query 2:

```
App\Models\Notification::whereDoesntHaveMorph('notifiable', '*')
    ->where('is_urgent', false)
    ->toRawSql();
```

Produces:

```
select * from "notifications" 
where 
(
    ("notifications"."notifiable_type" = 'App\Models\Video' and not exists (select * from "videos" where "notifications"."notifiable_id" = "videos"."id")) 
    or ("notifications"."notifiable_type" = 'App\Models\Post' and not exists (select * from "posts" where "notifications"."notifiable_id" = "posts"."id"))
) 
or "notifications"."notifiable_type" is null 
and "is_urgent" = 0"
```

These two queries return different results even though they are logically meant to be equivalent.

### Expected Behavior

The OR for the “no morph assigned” case (IS NULL) should be grouped together with the other per-type relation branches,
so it does not escape the relation condition group.
Both examples above should instead produce:

```
...
and (
  ("notifications"."notifiable_type" = 'App\Models\Video' and not exists (select * from "videos" where "notifications"."notifiable_id" = "videos"."id")) 
  or ("notifications"."notifiable_type" = 'App\Models\Post' and not exists (select * from "posts" where "notifications"."notifiable_id" = "posts"."id"))
  or "notifications"."notifiable_type" is null
)
```

### Actual behavior

The OR ... IS NULL is currently appended outside the grouping of the relation branches, which changes how
prior/subsequent where conditions bind and leads to inconsistent results.

### Code location and root cause

- File: `src/Illuminate/Database/Eloquent/Concerns/QueriesRelationships.php`
- Method: `hasMorph()`

#### Problem

- The per-type branches are correctly grouped inside a where(...) closure.
- The extra branch that handles the “null morph” case is appended afterwards.

## Steps to reproduce

A minimal reproduction repository is available here:

[Repo: [Eloquent] whereDoesntHaveMorph('relation', '*') generates ungrouped OR](https://github.com/hannrei/laravel-12-wheredoesnthavemorph-or-bug)

- Clone the repository and set it up. (see README)
- Open Tinker and execute the example queries to compare the generated SQL.

## Setup

0. Requirements

- Git
- Docker
- Composer

1. Clone the repository

    ```
   git clone git@github.com:hannrei/laravel-12-wheredoesnthavemorph-or-bug.git
    ```

2. Copy the `.env.example` file to `.env` by

    ```
    cp .env.example .env
    ```

3. Install dependencies by

    ```
    composer install
    ```

3. Start the application by

    ```
    ./vendor/bin/sail up -d
    ```

4. Generate app key with

   ```
   ./vendor/bin/sail artisan key:generate
   ```

5. Run

    ```
    ./vendor/bin/sail artisan migrate:fresh --seed
    ```
