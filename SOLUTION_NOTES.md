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
4. Added indexes migrations on `products` for common sort/filter patterns
5. Updated frontend frontend rating to read `reviews_avg_rating` also updated TypeScript product type to include 'reviews_avg_rating'

### Why these changes
1. Eager-loading category removes repeated per-row category lookups (N+1)
2. Aggreating review avoids loading full review records for each product
3. Product indexes support faster sorting and filtering becames easy 


---

## Task 2: Email Reliability

---

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