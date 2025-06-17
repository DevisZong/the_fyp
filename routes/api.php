<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChildAuth\ChildAuthController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\GrowthRecordsController;
use App\Http\Controllers\VaccinationController;
use App\Http\Controllers\HealthCareProviderController;
use App\Http\Controllers\VitaminAndDewormingController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\FoodFactController;
use App\Models\HealthCareProvider;
use PHPUnit\TextUI\Help;
use Spatie\Permission\Contracts\Role;

// require __DIR__.'/child.php';



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});
// Route::get('/children/childProfile', [ChildController::class, 'getChildProfile'])->name('api.children.childProfile');

// Children Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/children', [ChildController::class, 'index']);
    // Route::get('/children/{id}', [ChildController::class, 'show'])->name('api.children.show');
    Route::post('/children', [ChildController::class, 'store']);
    Route::put('/children/{id}', [ChildController::class, 'update']);
    Route::get('/children/childProfile', [ChildController::class, 'getChildProfile']);
    Route::get('/children/parentProfile', [ChildController::class, 'getParentProfile']);
    Route::get('/children/search', [ChildController::class, 'search']);
    // Route for fetching child details by ID

});

//Growth Records Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/growth-records', [GrowthRecordsController::class, 'index'])->name('api.growth-records.index');
    Route::get('/growth-records/{id}', [GrowthRecordsController::class, 'show'])->name('api.growth-records.show');
    Route::post('/growth-records/{childId}', [GrowthRecordsController::class, 'store'])->name('api.growth-records.store');
    Route::put('/growth-records/{id}', [GrowthRecordsController::class, 'update'])->name('api.growth-records.update');
    Route::put('/growth-records/update/{childId}', [GrowthRecordsController::class, 'update'])->name('api.growth-records.update-by-childId');
    Route::delete('/growth-records/{id}', [GrowthRecordsController::class, 'destroy'])->name('api.growth-records.destroy');
    Route::post('/growth-records/growth-chart', [GrowthRecordsController::class, 'getGrowthChart'])->name('api.growth-records.growth-chart');
});


//Children Routes
Route::post('/childLogin', [ChildAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'role:child'])->prefix('children')->group(function () {
    Route::get('/childProfile', [ChildController::class, 'getChildProfile']);
    Route::get('/parentProfile', [ChildController::class, 'getParentProfile']);
    Route::get('/appointments', [AppointmentController::class, 'getAppointments']);
    Route::get('/vaccination', [VaccinationController::class, 'showParent']);
    Route::get('/vitaminAndDeworming', [VitaminAndDewormingController::class, 'show']);
    Route::get('/growthChart', [GrowthRecordsController::class, 'getGrowthChartByToken']);
    Route::get('/growthSummary', [ChildController::class, 'growthRecordSummary']);
    Route::post('/change-password', [ChildAuthController::class, 'changePassword']);
    Route::post('/logout', [ChildAuthController::class, 'logout']);
});


//Admin Routes
Route::post('/adminLogin', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/children', [ChildController::class, 'index']);
    Route::get('/healthcare-providers', [HealthCareProviderController::class, 'index']);
    Route::post('/healthcare-providers', [HealthCareProviderController::class, 'store']);
    Route::put('/healthcare-providers/{id}', [HealthCareProviderController::class, 'update']);
    Route::delete('/healthcare-providers/{id}', [HealthCareProviderController::class, 'destroy']);
    Route::put('/child-update', [ChildController::class, 'update']);
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);
    Route::post('/search-users', [AuthController::class, 'searchUsers']);
    Route::post('/reset-password', [AuthController::class, 'resetUserPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});


//Healthcare Routes
Route::post('/healthcare-login', [AuthController::class, 'healthcareLogin'])->middleware('throttle:5,1');
Route::middleware(['auth:sanctum', 'role:nurse|doctor'])->prefix('healthcare')->group(function () {
    Route::get('/healthcare-profile', [HealthCareProviderController::class, 'showAuthenticated']);
    Route::get('/children', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
    //child-details file 
    Route::get('/children/{child}', [ChildController::class, 'show']);
    Route::get('/child-profile/{childId}', [ChildController::class, 'getHealthcareChildProfile']);
    //Growth Records Routes
    Route::post('/growth-records/{childId}', [GrowthRecordsController::class, 'store']);
    Route::get('/growth-details/{childId}', [GrowthRecordsController::class, 'getGrowthRecordDetails']);
    Route::put('/growth-records/update/{childId}', [GrowthRecordsController::class, 'update']);
    Route::get('/chart-data/{childId}', [GrowthRecordsController::class, 'getGrowthChartByChildId']);
    // Vaccination Routes
    Route::get('/vaccinations/{childId}', [VaccinationController::class, 'show']);
    Route::post('/vaccinations/store-with-verification', [VaccinationController::class, 'storeWithVerification']);
    Route::post('/vaccinations/verify-and-store', [VaccinationController::class, 'verifyAndStoreVaccination']);
    // vitamin and deworming
    Route::get('/vitamin-deworming/{childId}', [VitaminAndDewormingController::class, 'showByChildId']);
    Route::post('/vitamin-deworming/{childId}', [VitaminAndDewormingController::class, 'store']);
    //end
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});


// Dashboard Routes (Healthcare Provider Dashboard)
Route::middleware(['auth:sanctum', 'role:nurse|doctor'])->prefix('dashboard')->group(function () {
    // Dashboard statistics
    Route::get('/stats', [App\Http\Controllers\DashboardController::class, 'getStats']);

    // Registration trends for charts
    Route::get('/registration-trends', [App\Http\Controllers\DashboardController::class, 'getRegistrationTrends']);
    Route::get('/registration-trends-demo', [App\Http\Controllers\DashboardController::class, 'getRegistrationTrendsDemo']);

    // Create sample data for better visualization
    Route::post('/create-sample-data', [App\Http\Controllers\DashboardController::class, 'createSampleData']);

    // Children data for dashboard
    Route::get('/children', [ChildController::class, 'getDashboardChildren']);
    Route::get('/children/recent', [ChildController::class, 'getRecentChildren']);
    Route::get('/children/search', [ChildController::class, 'searchChildren']);

    // Appointments for dashboard
    Route::get('/appointments', [AppointmentController::class, 'getDashboardAppointments']);
    Route::get('/appointments/today', [AppointmentController::class, 'getTodayAppointments']);
});


// Activity Logs Route
Route::middleware(['auth:sanctum', 'role:admin'])->get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'getAppointments']);
    Route::post('/appointments/send-notifications', [AppointmentController::class, 'sendNotifications']);
    Route::get('/foodfacts', [FoodFactController::class, 'index']);
    Route::get('/foodfact/current-month', [FoodFactController::class, 'currentMonthDiet']);
});
