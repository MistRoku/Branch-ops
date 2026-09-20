@extends('components.layouts.guest')

@section('title', 'Privacy Policy - BranchOps Platform')

@section('content')
<div class="min-h-screen bg-brand-50 py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="/" class="text-sm text-accent-600 hover:underline">&larr; Back to Login</a>
            <h1 class="text-2xl font-bold text-brand-900 mt-4">Privacy Policy</h1>
            <p class="text-sm text-brand-600 mt-2">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <!-- Content -->
        <div class="card">
            <div class="prose prose-sm max-w-none">
                <h2 class="text-lg font-semibold mb-3">1. Information Collection</h2>
                <p class="mb-4 text-brand-700">
                    We collect information that you provide directly to us when you create an account, use the 
                    BranchOps Platform, or communicate with us. This may include:
                </p>
                <ul class="list-disc pl-5 mb-4 text-brand-700">
                    <li>Name and email address</li>
                    <li>Account credentials</li>
                    <li>Branch and role assignments</li>
                    <li>Transaction and usage data</li>
                    <li>Device and log information</li>
                </ul>

                <h2 class="text-lg font-semibold mb-3">2. Use of Information</h2>
                <p class="mb-4 text-brand-700">
                    We use the information we collect to:
                </p>
                <ul class="list-disc pl-5 mb-4 text-brand-700">
                    <li>Provide, maintain, and improve the BranchOps Platform</li>
                    <li>Process transactions and send related information</li>
                    <li>Send technical notices and support messages</li>
                    <li>Respond to your comments and questions</li>
                    <li>Protect against fraudulent or illegal activity</li>
                </ul>

                <h2 class="text-lg font-semibold mb-3">3. Data Sharing</h2>
                <p class="mb-4 text-brand-700">
                    We do not sell, trade, or otherwise transfer your personal information to outside parties 
                    except in the following circumstances:
                </p>
                <ul class="list-disc pl-5 mb-4 text-brand-700">
                    <li>With your explicit consent</li>
                    <li>To comply with legal obligations</li>
                    <li>To protect our rights and safety</li>
                    <li>With service providers who assist in platform operations</li>
                </ul>

                <h2 class="text-lg font-semibold mb-3">4. Data Security</h2>
                <p class="mb-4 text-brand-700">
                    We implement appropriate technical and organizational measures to protect your personal 
                    information against unauthorized access, alteration, disclosure, or destruction. However, 
                    no method of transmission over the internet is 100% secure.
                </p>

                <h2 class="text-lg font-semibold mb-3">5. Data Retention</h2>
                <p class="mb-4 text-brand-700">
                    We retain personal information for as long as necessary to fulfill the purposes outlined in 
                    this policy, unless a longer retention period is required by law. Audit logs are retained 
                    for a minimum of 2 years for compliance purposes.
                </p>

                <h2 class="text-lg font-semibold mb-3">6. Your Rights</h2>
                <p class="mb-4 text-brand-700">
                    Depending on your location, you may have the following rights regarding your personal 
                    information:
                </p>
                <ul class="list-disc pl-5 mb-4 text-brand-700">
                    <li>Access to your personal data</li>
                    <li>Correction of inaccurate data</li>
                    <li>Deletion of your data (subject to legal obligations)</li>
                    <li>Data portability</li>
                    <li>Restriction of processing</li>
                </ul>

                <h2 class="text-lg font-semibold mb-3">7. Cookies</h2>
                <p class="mb-4 text-brand-700">
                    The BranchOps Platform uses essential cookies to maintain session state and ensure proper 
                    functionality. These cookies are necessary for the platform to operate correctly.
                </p>

                <h2 class="text-lg font-semibold mb-3">8. Third-Party Services</h2>
                <p class="mb-4 text-brand-700">
                    Our platform may integrate with third-party services for functionality such as payment 
                    processing, email delivery, or cloud storage. These services have their own privacy policies 
                    and terms of service.
                </p>

                <h2 class="text-lg font-semibold mb-3">9. Children's Privacy</h2>
                <p class="mb-4 text-brand-700">
                    The BranchOps Platform is not intended for children under 13 years of age. We do not 
                    knowingly collect personal information from children under 13.
                </p>

                <h2 class="text-lg font-semibold mb-3">10. Changes to This Policy</h2>
                <p class="mb-4 text-brand-700">
                    We may update this privacy policy from time to time. We will notify you of any changes by 
                    posting the new policy on this page and updating the "Last updated" date.
                </p>

                <h2 class="text-lg font-semibold mb-3">11. Contact Us</h2>
                <p class="mb-4 text-brand-700">
                    If you have any questions about this privacy policy or our data practices, please contact 
                    us at privacy@branchops.example.com.
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
