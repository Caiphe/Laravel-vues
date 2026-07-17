# Solution Notes & Justifications

Please use this file to document your approach, decisions, and justifications for the tasks you've completed. This helps us understand your thought process and the reasoning behind your implementation choices.

## Task 1: Performance Investigation
1. The catalogue index wuery loaded full `reviews` data for every lissted products, which increased payload size and query cost for the a list page
2. the controller accessed `category` in a loop without eager-loading `category` which caused N+1 query
3. Included database indexing 

### Change implemented
1. Updated products index query with eager-load category data in the base query.
2. Replaced full review on the catalogue endpoint with aggregates:  `withCount(reviews)` and `withAvg('reviews', 'rating')`
3. Search grouping in a closure this is just my preference 
4. Added indexes migrations on products for common sort/filter patterns
5. Updated frontend frontend rating to read `reviews_avg_rating` also updated TypeScript product type to include 'reviews_avg_rating'

### Why these changes
1. Eager-loading category removes repeated per-row category lookups (N+1)
2. Aggreating review avoids loading full review records for each product
3. Product indexes support faster sorting and filtering becames easy 

---

## Task 2: Email Reliability

1. Checkout previously called email sending only once and did not react to a false return value.
2. This meant orders could be created while confirmation emails were missed or did not send.

### Change Implemnented
1. I Kept the EmailService failure untouched as required
2. I moved confirmation email sending to a queued job (`SendOrderConfirmationEmailJob`) dispatched from `CheckoutController`.
3. I configured retry handling in the job using queue retries/backoff (up to 3 attempts).
4. I updated checkout flow to dispatch the job instead of sending email inline in the request lifecycle.
5. Included explicit logging when all retry attempts fail (in job failure handling) to improve observability and troubleshooting. 

### Why these changes ?
1. Since subsequent attempts usually succeed, queued retries directly address the known failure pattern.
2. Logging exhausted retries ensure support team can investigate persistent delivery failures 
3. We don't control the external API, and sometimes it fails randomly, but the app handles this safely by dispatching asynchronous retries without blocking checkout.

### OUtcome 
1. Order confirmations are resilient to first-attempt email failures.
2. The order flow remains functional while email delivery is retried asynchronously via queue workers. 
---

## Task 3: Order History Page
1. Authenticated customers had no dedicated page to review prior purchases after checkout.
2. Existing order detail visibility was tied to checkout confirmation
3. Navigation did not expose a direct "My Orders" url for signed-in users.

### Changed implemented 
1. Added a new `OrderController` with two authenticated actions:
	- `index`: returns only the authenticated user's orders, paginated at 10 per page.
	- `show`: authorizes access to the order owner only (403 is returned if the order does not belong to the signed user).

2. Added and wired authorization policy for orders:
	- Created `OrderPolicy` with `viewAny` and `view` ownership checks.
	- Registered policy mapping in `AppServiceProvider`.
	- Added authorization calls in `OrderController` (`viewAny` for list, `view` for single order).

3. Enabled controller authorization helpers by adding `AuthorizesRequests` trait to base `Controller`.

4. Added frontend pages for order history and single order details:
	- `OrderHistory` page with order list, status badges, pagination, and links to each order detail, had to make sure it uses the same design as the rest of the features.
	- `OrderDetails` page with customer info, shipping details, itemized products, and totals (well layout out for easy presentation).
5. I added `My Orders` entry in the authenticated user dropdown menu in the header.

6. Added feature test coverage:
	- Guest access redirects to login for order routes.
	- Authenticated users only see their own orders.
	- Order owner can view details.
	- Non-owner receives `403` on direct order access (this is achieved via policy).

### Why these changes
1. Policy-based authorization prevents insecure direct object access while keeping controller logic clean (I first used `whereBelongsTo($request->user())` for both methods in the orderController but for a `good architecture` I chose to use the `policy` ).

2. Route model binding by `order_number` keeps URLs user-friendly and consistent with domain identifiers (it's also clean).


### Outcome
1. Signed-in users can access a clear order pages from the main navigation ().
2. Order data is restricted to the correct owner end-to-end (routing, controller, and policy).
3. Behavior of these features is verified with dedicated feature tests for both happy path and forbidden access.


## General Notes

### Technical Decisions
- Task 1 (Performance): kept aggregate review metrics in the catalogue query (`withCount`, `withAvg`) while removing full review eager loading to reduce payload size and avoid unnecessary hydration on list pages.
- Task 1 (Performance): kept category eager loading on the catalogue endpoint to avoid N+1 access when rendering category metadata (including `category_color`).
- Task 1 (Performance): added product table indexes for high-traffic sort/filter paths to improve query execution for catalogue browsing.

- Task 2 (Email Reliability): I chose asynchronous email dispatch via a queued job to keep checkout non-blocking while handling flaky third-party delivery.
- Task 2 (Email Reliability): Preserved checkout success flow even when email retries are exhausted, while adding explicit error logging for observability and support follow-up.
- Task 2 (Email Reliability): Kept `EmailService` unchanged and moved reliability handling to application orchestration through queue retries/backoff.

- Task 3 (Order History): Implemented ownership authorization via `OrderPolicy` + controller `authorize()` calls instead of relying only on query filtering, to enforce access control as a first-class architectural rule.
- Task 3 (Order History): Used route model binding with `order_number` for cleaner, user-facing URLs and simpler controller signatures.
- Task 3 (Order History): Added dedicated history and detail pages (`OrderHistory`, `OrderDetails`) and surfaced "My Orders" in authenticated navigation to improve discoverability.

### Testing Approach
- Task 1 (Performance): Verified catalogue behavior by checking search/category/sort flows still returned correct results after query refactor, and ensured review averages display in frontend reads from `reviews_avg_rating` with clean formatting (`3` / `3.5`).
- Task 1 (Performance): Verified reduced query count and absence of N+1 behavior using Laravel Debugbar.
- Task 1 (Performance): Ran existing ProductCard frontend tests after rating changes to validate no UI regressions in rating rendering and card interactions.

- Task 2 (Email Reliability): Validated queued email behavior through checkout execution, database jobs inspection, worker processing (`queue:work --queue=emails`), and log inspection for retry/failure flow.
- Task 2 (Email Reliability): Verified checkout still completes order creation even when email retries fail, preserving core transaction flow.

- Task 3 (Order History): Added and ran dedicated feature tests for `OrderController` covering guest redirect to login, owner-only order visibility, successful owner access to order details.
- Task 3 (Order history): Logged in with two different users (A, B), created orders for each user (A, B), and try to access the order of the user A from user B account and got the 403 error message.(Authorisation and Pocily tsting)
- Task 3 (Order History): Manually verified authenticated navigation includes "My Orders" and that list-to-detail navigation works using `order_number` routes.

### Future Considerations
- For production hardening, I would run dedicated/supervised workers for the `emails` queue with alerting and autoscaling to handle spikes reliably.

- Add structured monitoring for email reliability (success/failure rates, retry counts, alerting thresholds) to reduce manual log inspection overhead.

- Add integration-level tests for order history pagination edge cases (large datasets, last page behavior, empty states) and UI consistency snapshots.

- Consider introducing separate environment presets for host vs Docker DB/mail configuration to avoid local setup drift and reduce startup friction.

---

## Questions or Clarifications

To run the worker with sail use this command : 
`./vendor/bin/sail artisan queue:work --queue=emails`