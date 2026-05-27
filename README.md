# Silicon Xchange Signal Engine

Laravel + Blade implementation of a trust-weighted discovery engine for Africa tech and venture voices. The app is database-backed and includes discovery, authenticated recommendations, moderation, ranking logic, score transparency, and audit history.

## Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Open `http://127.0.0.1:8000`.

Seeded admin:

```text
admin@siliconxchange.tech / password
```

The login page includes a local assessment-mode login. Production LinkedIn sign-in is wired through Laravel Socialite:

```bash
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
LINKEDIN_REDIRECT_URI="${APP_URL}/auth/linkedin/callback"
```

## Built Features

Discovery:

- Ranked feed by trust score.
- Dense rows with score, credibility summary, recommendation count, confidence, provenance, tags, and admin-only conflict badge.
- Filters by geography, topic, and format.
- Sort by trust score, most recent, or most recommended.
- Keyword and expanded search using stored profile vectors, reranked by trust score.

Profiles:

- Full profile pages with bio, platform link, score, confidence, provenance, geography/topic/format pills, all recommendations, role badges, and similar voices.
- Score explainer endpoint at `/profiles/{profile}/score` with per-recommendation role weight, decay, verification, conflict status, and contribution.

Auth:

- Laravel Socialite LinkedIn OAuth routes.
- First-login role selection.
- No anonymous recommendations or submissions.

Recommendations:

- Logged-in users only.
- Mandatory rationale.
- Recommender role/company captured at time of recommendation.
- Trust score recalculates after every recommendation.
- Credibility summary regenerates when a profile reaches three or more recommendations.

Submissions:

- Authenticated public submission form.
- Bio-based tag suggestions.
- Controlled tag vocabulary only.
- Pending review badge until admin approval.

Trust and integrity:

- Weighted role scoring with investor/founder/operator weighting.
- Freshness decay per recommendation.
- Conflict detector for reciprocal recommendations and same-company recommendations.
- Conflict penalty affects score unless admin overrides it.
- Duplicate detector with fuzzy name similarity and profile-vector similarity.
- Profile data quality score out of 10 with missing-data notes.
- Full non-deletable audit log table.

Admin:

- Approval queue with approve/reject/edit/bulk actions.
- Duplicate alert queue with side-by-side diff, merge, and dismiss.
- Conflict flags queue with confirm/override.
- Audit log.
- Data quality rescans.

## Ranking Logic

Each recommendation contributes to a profile score using:

- Role weight: investor `1.6`, founder `1.35`, operator `1.2`, researcher/journalist/policy `1.1`, general user `1.0`.
- Verified identity multiplier: verified recommenders receive a small lift.
- Freshness decay: older recommendations gradually contribute less than recent ones.
- Conflict penalty: reciprocal or same-company recommendations are flagged and penalized unless an admin overrides the flag.

The profile confidence label is based on recommendation volume: high for three or more sources, medium for two, and low for one or fewer. The score explainer page exposes the breakdown so a user can see why a profile ranks where it does.

## Integrity Notes

Gaming prevention:

- No anonymous vouching.
- One recommendation per user/profile pair.
- Role, company, verification status, decay, and conflict state are stored with each recommendation.
- Admins can confirm or override conflict flags, and every action lands in the audit log.

Freshness:

- Decay is applied during score recalculation, so stale recommendations do not permanently lock a profile at the top.
- New recommendations can revive a profile only when tied to an accountable professional identity and rationale.

Signal verification:

- The current prototype verifies accountability through authenticated identity, role selection, recommender company capture, provenance, duplicate detection, and moderation.
- Cross-source verification can be added as another integrity input without changing the public feed model.

Long-term moat:

- The durable asset is not a content feed; it is the vetted relationship graph of who recommends whom, why they recommend them, and how reliable that pattern has been over time.

## 30/90/180-Day Roadmap

30 days:

- Harden LinkedIn OAuth and add stronger professional role verification.
- Improve moderation flows for duplicates, metadata edits, and conflict review.
- Add clearer provenance fields for seeded profiles and direct submissions.

90 days:

- Validate signal with cross-platform citations, newsletters, podcasts, and public professional mentions.
- Improve ranking with source diversity, network patterns, and stronger anti-gaming penalties.
- Add admin analytics for emerging voices and suspicious recommendation clusters.

180 days:

- Grow the product into an ecosystem intelligence layer for investors, founders, accelerators, and media teams.
- Add partner APIs and embeddable discovery widgets.
- Build the long-term moat around the trusted graph of who recommends whom, why, and how that signal changes over time.

## Scheduled Pulse

Weekly pulse is generated by:

```bash
php artisan signal:pulse
```

It is scheduled every Monday at 08:00 in `routes/console.php`.
