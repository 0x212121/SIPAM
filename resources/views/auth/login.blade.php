@extends('layouts.app')

@section('title', 'Login - Audit Evidence Map')

@section('content')
<div class="card" style="max-width: 400px; margin: 100px auto;">
    <h2 style="text-align: center; margin-bottom: 30px;">Audit Evidence Map</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                Remember me
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
    </form>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
        <p><strong>Test Accounts:</strong></p>
        <table style="width: 100%; font-size: 11px;">
            <tr>
                <td>admin@audit.local</td>
                <td>admin123</td>
            </tr>
            <tr>
                <td>auditor@audit.local</td>
                <td>auditor123</td>
            </tr>
            <tr>
                <td>reviewer@audit.local</td>
                <td>reviewer123</td>
            </tr>
            <tr>
                <td>readonly@audit.local</td>
                <td>readonly123</td>
            </tr>
        </table>
    </div>
</div>
@endsection
