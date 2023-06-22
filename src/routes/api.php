<?php

use Illuminate\Support\Facades\Route;

Route::post('receive', function (\Illuminate\Http\Request $request){
    return $request->all();
});

//******* La pongo aqui para no tener que loguearme mientras desarrollo ********/

/**** Fin sección de routes para desarrollar ***/

Route::post('login', 'UserController@login')->name('login');
Route::post('sendLinkResetPassword', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::match(['GET', 'POST'],'reset/{token}', 'Auth\ResetPasswordController@reset');

//Reporting-ranking
Route::post('reports/ranking-efficiency', 'ReportingController@rankingByEfficiency');
Route::post('reports/ranking-first3', 'ReportingController@rankingFirst3Places');

Route::group(['middleware' => 'auth:api'], function(){

    Route::match(['get', 'post'], 'setup-password', 'UserController@setupPassword')->name('setup-password');

    Route::get('brands/list', 'BrandsController@list');
    Route::get('groups/list', 'GroupsController@list');
    Route::get('lines/list', 'LinesController@searchLinesByGroup');
    Route::get('units/list', 'UnitsController@list');
    Route::get('store/list', 'StoresController@list');

    Route::post('reports/scans', 'ReportingController@scans');
    Route::post('reports/scans-csv', 'ReportingController@ScansToCsv');
    Route::post('reports/products', 'ReportingController@Products');
    Route::get('reports/products/{barcode}', 'ReportingController@productByBarcode');
    Route::post('reports/products-csv', 'ReportingController@ProductsToCsv');
    Route::get('reports/average-prices/{barcode}', 'ReportingController@dataGraphAverage');
    Route::post('reports/history-summary/{barcode}', 'ReportingController@historySummary');
    Route::post('reports/history-details', 'ReportingController@historyDetails');
    Route::get('reports/history-prices/{barcode}', 'ReportingController@historyDetailsByBarcode');
    Route::post('reports/ranking', 'ReportingController@ranking');
    Route::post('reports/ranking-validators', 'ReportingController@rankingValidator');
    Route::post('reports/score-scans', 'ReportingController@scoreScans');

    Route::get('product/show', 'ProductsController@showProducts');
    Route::get('product/{barcode}', 'ProductsController@show');

    Route::get('scan/{scan}/show', 'ValidationScreenController@showScan');

    Route::get('ranking/user', 'ScansController@rankingByUser');
    Route::get('ranking/mission', 'ScansController@rankingByMission');
    Route::get('active-missions', 'MissionsController@activeMissions');
    Route::get('pending/scans/user/{id}', 'ScansController@pendingScansByUser');
    Route::post('pics/user/{id}', 'PicturesController@saveUserImage');
    Route::post('pics/settings-icon', 'PicturesController@saveLogoImage');
    Route::post('pics/chain/{id}', 'PicturesController@saveChainImage');

    Route::get('criterion/list', 'RejectionCriteriaController@list');
    Route::post('criterion/create', 'RejectionCriteriaController@store');
    Route::get('criterion/search', 'RejectionCriteriaController@show');
    Route::put('criterion/update/{id}', 'RejectionCriteriaController@update');
    Route::post('criterion/scan/{id}', 'RejectionCriteriaController@assignCriteria');

    Route::post('fcm/token', 'FirebaseController@saveToken');
    Route::post('fcm/notification', 'FirebaseController@sendGeneralNotification');
    Route::get('notification/{id}', 'FirebaseController@show');

    Route::get('theme', 'ThemeController@show');

    Route::group(['middleware' => ['role:Validador|Admin|Scanner']], function () {

        Route::get('users/listing', 'UserController@listing')->name('user-listing');
        Route::get('users/csv', 'UserController@usersToCsv')->name('user-usersToCsv');
        Route::get('users/listid', 'UserController@listById')->name('user-listid');
        Route::post('users/{id}/edit', 'UserController@update')->name('user-update');
        Route::get('users/{id}', 'UserController@show')->name('user-show');
        Route::delete('users/{id}', 'UserController@trash')->name('user-trash');
        Route::post('users/{id}', 'UserController@restore')->name('user-restore');
        Route::apiResource('labels', 'LabelController');
        Route::post('labels/{user}/addLabel', 'LabelController@addLabelsToUser')->name('addLabels');
        Route::post('labels/{label}/restore', 'LabelController@restore')->name('label-restore');

        Route::get('regions', 'RegionController@index');
        Route::post('regions/{user}/addRegion', 'RegionController@addRegionsToUser')->name('addRegions');
        Route::post('regions/{region}/restore', 'RegionController@restore')->name('region-restore');
        Route::apiResource('roles', 'RoleController');
        Route::post('roles/{user}/assignRole', 'RoleController@assignRoleToUser')->name('assignRole');
        Route::post('register', 'UserController@registerUser')->name('register');
        Route::post('leaderboard', 'UserController@leaderboard')->name('leaderboard');
        Route::get('user/{id}', 'UserController@singleUser')->name('user-id');

            //Catalogs
                //  ::  Brands  :: \\
        Route::get('brands/search', 'BrandsController@show');
        Route::get('brands/csv', 'BrandsController@brandsToCsv');
        Route::post('brands/create', 'BrandsController@store');
        Route::put('brands/update/{id}', 'BrandsController@update');
        Route::delete('brands/delete/{id}', 'BrandsController@destroy');
        Route::apiResource('brands', 'BrandsController');
                //  ::  Groups  :: \\
        Route::get('groups/search', 'GroupsController@show');
        Route::get('groups/csv', 'GroupsController@groupsToCsv');
        Route::post('groups/create', 'GroupsController@store');
        Route::put('groups/update/{id}', 'GroupsController@update');
        Route::delete('groups/delete/{id}', 'GroupsController@destroy');
                //  ::  Lines  :: \\
        Route::get('lines/search', 'LinesController@show');
        Route::get('lines/csv', 'LinesController@linesToCsv');
        Route::post('lines/create', 'LinesController@store');
        Route::put('lines/update/{id}', 'LinesController@update');
        Route::delete('lines/delete/{id}', 'LinesController@destroy');
                //  ::  Units  :: \\

        Route::get('units/search', 'UnitsController@show');
        Route::post('units/create', 'UnitsController@store');
        Route::put('units/update/{id}', 'UnitsController@update');
        Route::delete('units/delete/{id}', 'UnitsController@destroy');
        Route::apiResource('units', 'UnitsController');
                //  ::  Region  :: \\
        Route::get('regions/search', 'RegionController@show');
        Route::get('regions/csv', 'RegionController@regionToCsv');
        Route::post('regions/create', 'RegionController@store');
        Route::put('regions/update/{id}', 'RegionController@update');
        Route::delete('regions/delete/{id}', 'RegionController@destroy');
                //  ::  Chain  :: \\
        Route::get('chains/search', 'ChainsController@show');
        Route::get('chains/csv', 'ChainsController@chainsToCsv');
        Route::post('chains/create', 'ChainsController@store');
        Route::put('chains/update/{id}', 'ChainsController@update');
        Route::delete('chains/delete/{id}', 'ChainsController@destroy');
                //  ::  Language  :: \\
        Route::get('languages/search', 'LanguagesController@show');
        Route::post('languages/create', 'LanguagesController@store');
        Route::put('languages/update/{id}', 'LanguagesController@update');
        Route::delete('languages/delete/{id}', 'LanguagesController@destroy');
        Route::apiResource('languages', 'LanguagesController');

            //products
        Route::post('product/{barcode}/update', 'ProductsController@update');
        Route::post('product/{barcode}/updatePhoto', 'ProductsController@replacePhoto');
        Route::delete('product/{barcode}', 'ProductsController@destroy');
        Route::put('product/{id}/update-id', 'ProductsController@updateProductById');

            //App scan
        Route::get('scan/user', 'ValidationScreenController@appScanList');
            //Validation Screen
        Route::post('scan/{scan}/update', 'ValidationScreenController@updateScan');
        Route::delete('scan/{scan}', 'ValidationScreenController@destroy');
        Route::put('scan/{id}/update-id', 'ScansController@updateScanById');
        Route::post('pics', 'PicturesController@save')->name('pics');
        Route::post('scan', 'ValidationScreenController@saveScan')->name('saveScan');
        Route::get('scan/all', 'ValidationScreenController@listScans');
        Route::get('scan/{scan}', 'ValidationScreenController@getScan');
        Route::get('scan/{scan}/barcode/{barcode}', 'ValidationScreenController@simulateScan');
        Route::post('scan/{scan}/updatePictureProduct', 'ValidationScreenController@updatePictureProductScan');
        Route::post('scan/being-validated', 'ValidationScreenController@scanBeingValidated');

        Route::apiResource('store', 'StoresController');
        Route::post('scan/{scan}', 'ValidationScreenController@validateScan');
        Route::get('scan/{scan}/rejected', 'ValidationScreenController@rejectedScan');
        Route::get('store/findLocation', 'StoresController@findByLocation');

            //Missions
        Route::post('missions/{mission}/update', 'MissionsController@update');
        Route::get('missions/all', 'MissionsController@all');
        Route::post('missions/list', 'MissionsController@list');
        Route::get('missions/list-validation', 'MissionsController@listValidation');
        Route::apiResource('missions', 'MissionsController');
        Route::get('master-file/import', 'ThreeBProductsController@isThereAnImportedCSV');
        Route::post('master-file/import', 'ThreeBProductsController@importMasterFile');
        Route::post('master-file/compare', 'ThreeBProductsController@comparePrices');
    });

    Route::group(['middleware' => ['role:Admin|Analista']], function () {
        Route::get('reports/scans-analyst', 'ReportingController@scansAnalyst');
        Route::get('reports/scans-csv-analyst', 'ReportingController@scansToCsvAnalyst');
        Route::get('reports/products-analyst', 'ReportingController@productsAnalyst');
        Route::get('reports/products-csv-analyst', 'ReportingController@productsToCsvAnalyst');
        Route::post('list-products', 'ListProductsController@store');
        Route::get('list-products', 'ListProductsController@list');
        Route::get('reports/products-list', 'ReportingController@productsAnalystList');
        Route::delete('list-products', 'ListProductsController@destroy');
        Route::get('reports/users-scanner', 'ReportingController@usersScanner');
        Route::get('reports/missions-region', 'ReportingController@missionsByRegion');
        Route::get('reports/user-missions/{scanner}', 'ReportingController@userMissions');
                //  ::  Setting  :: \\
        Route::put('settings/update', 'SettingsController@update');
    });

    Route::group(['middleware' => ['role:Admin']], function () {
        Route::post('theme', 'ThemeController@store');
        Route::post('theme-logo', 'ThemeController@uploadLogo');
    });
});

Route::fallback(function () {
    return response()->json(['error' => 'Not Found!'], 404);
});
