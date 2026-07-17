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
---
1. Checkout previously called email sending only once and did not react to a false return value.
2. This meant orders could be created while confirmation emails were missed or did not send.

### Change Implemnented
1. I Kept the EmailService failure untouched as required
2. I also added application-level retry handling in `CheckoutCOntroller` via the method `sendOrderConfirmationWithRetry`.
3. Included a configuration of retries attempt up to 3 times with a little delay between atempts (`300ms`)
4. I updated the checkout flow to call the retry wrapper instead of a single email send call
5. Include and explicit logging when all retry attempts fail, this is just to improve observability also support troubleshooting. 

### Why these changes
1. Since subsequent attempts usually succeed, retries directly address the known failure pattern.
2. Logging exhausted retries ensure support team can investigate persistent delivery failures 
3. We don't control the external API, and sometimes it fails randomly, But our app should be designed to hand those failures safely by managing the flow around it (for example: retrying which what was implemented )

### OUtcome 
1. Order confirmations are resilient to first-attempt email failures.
2. The order flow remain functional while email delivery is retired automatically. 

## Task 3: Order History Page

---

## General Notes

### Technical Decisions
- [Any other important technical decisions you made]
- [Architecture choices, design patterns used, etc.]

### Testing Approach
- [How did you test your solutions?]

### Future Considerations
- [What would you improve or change if you had more time?]
- [Any potential issues or limitations with your current implementation?]

---

## Questions or Clarifications

If you have any questions about the requirements or would like to clarify any of your implementation choices, please include them here. 