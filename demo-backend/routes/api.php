<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::delete('/expenses/{transaction}', [ExpenseController::class, 'destroy']);

    Route::get('/incomes', [IncomeController::class, 'index']);
    Route::post('/incomes', [IncomeController::class, 'store']);
    Route::delete('/incomes/{transaction}', [IncomeController::class, 'destroy']);

    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy']);

    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy']);
    Route::get('/goals/{goal}/contributions', [GoalController::class, 'contributions']);
    Route::post('/goals/{goal}/contributions', [GoalController::class, 'storeContribution']);

    Route::get('/recurring', [RecurringTransactionController::class, 'index']);
    Route::post('/recurring', [RecurringTransactionController::class, 'store']);
    Route::delete('/recurring/{recurring}', [RecurringTransactionController::class, 'destroy']);

    Route::get('/summary', SummaryController::class);
});
