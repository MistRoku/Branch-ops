@extends('components.layouts.guest')

@section('title', 'Terms of Service - BranchOps Platform')

@section('content')
<div class="min-h-screen bg-brand-50 py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="/" class="text-sm text-accent-600 hover:underline">&larr; Back to Login</a>
            <h1 class="text-2xl font-bold text-brand-900 mt-4">Terms of Service</h1>
            <p class="text-sm text-brand-600 mt-2">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <!-- Content -->
        <div class="card">
            <div class="prose prose-sm max-w-none">
                <h2 class="text-lg font-semibold mb-3">1. Acceptance of Terms</h2>
                <p class="mb-4 text-brand-700">
                    By accessing and using the BranchOps Platform, you accept and agree to be bound by the terms 
                    and provision of this agreement. If you do not agree to abide by these terms, please do not 
                    use this service.
                </p>

                <h2 class="text-lg font-semibold mb-3">2. Use License</h2>
                <p class="mb-4 text-brand-700">
                    Permission is granted to temporarily access the BranchOps Platform for personal or internal 
                    business use only. This is the grant of a license, not a transfer of title, and under this 
                    license you may not:
                </p>
                <ul class="list-disc pl-5 mb-4 text-brand-700">
                    <li>Modify or copy the materials</li>
                    <li>Use the materials for any commercial purpose or public display</li>
                    <li>Attempt to decompile or reverse engineer any software contained in the platform</li>
                    <li>Remove any copyright or other proprietary notations from the materials</li>
                    <li>Transfer the materials to another person or mirror the materials on any other server</li>
                </ul>

                <h2 class="text-lg font-semibold mb-3">3. User Accounts</h2>
                <p class="mb-4 text-brand-700">
                    You are responsible for maintaining the confidentiality of your account credentials and for 
                    all activities that occur under your account. You must immediately notify us of any 
                    unauthorized use of your account.
                </p>

                <h2 class="text-lg font-semibold mb-3">4. Data and Privacy</h2>
                <p class="mb-4 text-brand-700">
                    Your use of the BranchOps Platform is also governed by our Privacy Policy. Please review our 
                    Privacy Policy to understand our practices regarding the collection and use of your personal 
                    information.
                </p>

                <h2 class="text-lg font-semibold mb-3">5. Disclaimer</h2>
                <p class="mb-4 text-brand-700">
                    The BranchOps Platform is provided "as is" without any warranties, expressed or implied, 
                    including but not limited to the implied warranties of merchantability, fitness for a 
                    particular purpose, or non-infringement.
                </p>

                <h2 class="text-lg font-semibold mb-3">6. Limitations</h2>
                <p class="mb-4 text-brand-700">
                    In no event shall BranchOps or its suppliers be liable for any damages (including, without 
                    limitation, damages for loss of data or profit, or due to business interruption) arising out 
                    of the use or inability to use the BranchOps Platform.
                </p>

                <h2 class="text-lg font-semibold mb-3">7. Revisions</h2>
                <p class="mb-4 text-brand-700">
                    We may revise these terms of service at any time without notice. By using this platform you 
                    are agreeing to be bound by the then current version of these terms of service.
                </p>

                <h2 class="text-lg font-semibold mb-3">8. Governing Law</h2>
                <p class="mb-4 text-brand-700">
                    These terms and conditions are governed by and construed in accordance with applicable laws 
                    and you irrevocably submit to the exclusive jurisdiction of the courts in that location.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-xs text-brand-500">
                &copy; {{ date('Y') }} BranchOps Platform. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection
