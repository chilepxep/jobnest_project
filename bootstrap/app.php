<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; 
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->alias([
            'employer.approved'     => \App\Http\Middleware\CheckEmployerApproved::class,  
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            $role = $request->user()?->role;

            return match ($role) {
                'employer'  => '/employer/dashboard',
                'candidate' => '/candidate/dashboard',
                'admin'     => '/admin/dashboard',
                default     => '/', 
            };
        });

    })
   ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return redirect()->back()
                ->withInput($request->except('password', '_token'))
                ->with('error', 'Phiên làm việc đã hết hạn do bạn treo máy quá lâu. Vui lòng thao tác lại.');
    });
    })->create();