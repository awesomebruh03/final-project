Backend requirements (the whole list)

Conventions that apply to everything:

\- All responses are JSON

\- All endpoints live under /api (e.g. http://localhost:8080/api/...)

\- Errors always look like { "message": "..." } with a normal status code: 400 bad input, 401 not logged in, 404 not found

\- If you run the backend on a different port than the frontend during dev, enable CORS for http://localhost:5173 (with credentials, if you use cookies)

1\. Auth

POST /api/register — creates account and logs the user in

// request

{ "name": "Sara", "email": "sara@example.com", "password": "123456" }



// response (200)

{ "user": { "id": 1, "name": "Sara", "email": "sara@example.com" } }

\- 409 with a message if the email is already registered

\- Hash the password before saving (bcrypt). Never send it back in any response.

POST /api/login

// request

{ "email": "sara@example.com", "password": "123456" }



// response (200) — same user object as register

{ "user": { "id": 1, "name": "Sara", "email": "sara@example.com" } }

\- 401 with { "message": "Wrong email or password" } — keep it vague, don't reveal which one was wrong

POST /api/logout → { "ok": true }

Sessions: after login/register, remember who's calling. Two options, tell me which you did:

\- Session cookie (e.g. express-session) — simplest for the frontend, browser sends it automatically, needs CORS credentials: true

\- Token (e.g. JWT) — frontend stores it in localStorage and sends Authorization: Bearer <token> on every expense request

2\. Expenses

GET /api/expenses — returns the logged-in user's expenses (401 if not logged in)

\[

&#x20; { "id": 7, "amount": 250.5, "date": "2026-11-08", "category": "Food", "note": "lunch with friends" }

]

Order doesn't matter — frontend sorts.

POST /api/expenses — creates one, returns it with its id

// request

{ "amount": 250.5, "date": "2026-11-08", "category": "Food", "note": "lunch" }



// response (201)

{ "id": 7, "amount": 250.5, "date": "2026-11-08", "category": "Food", "note": "lunch" }

\- Validate: amount is a positive number, date is a real date, category is not empty → 400 with a message otherwise

DELETE /api/expenses/:id — deletes one of the user's own expenses → { "ok": true }

\- 404 if it doesn't exist or belongs to someone else (never delete other people's stuff)

3\. Exact data shapes

Expense:

Field	Type	Notes

id	number or string	whatever's easier, frontend just echoes it back on delete

amount	number	a real number, not a string

date	string	exactly "YYYY-MM-DD" — no timestamps, avoids timezone bugs

category	string	free text, max \~50 chars (frontend handles the dropdown + custom option, backend just stores it)

note	string	optional — "" or missing is fine

User: id, name, email — that's it, never the password.



