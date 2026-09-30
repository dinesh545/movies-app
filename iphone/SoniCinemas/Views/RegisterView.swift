import SwiftUI

struct RegisterView: View {
    @Environment(\.presentationMode) var presentationMode
    var onRequireOtp: (String) -> Void
    
    @State private var name = ""
    @State private var mobile = ""
    @State private var email = ""
    @State private var password = ""
    @State private var isLoading = false
    @State private var errorMessage: String? = nil
    
    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            
            VStack(spacing: 20) {
                HStack {
                    Text("Register Account")
                        .font(.title2)
                        .fontWeight(.bold)
                        .foregroundColor(.white)
                    Spacer()
                    Button(action: { presentationMode.wrappedValue.dismiss() }) {
                        Image(systemName: "xmark.circle.fill")
                            .font(.title2)
                            .foregroundColor(.gray)
                    }
                }
                .padding(.horizontal)
                .padding(.top)
                
                ScrollView {
                    VStack(spacing: 15) {
                        TextField("Full Name", text: $name)
                            .padding()
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                        
                        TextField("Mobile Number", text: $mobile)
                            .keyboardType(.phonePad)
                            .padding()
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                        
                        TextField("Email Address", text: $email)
                            .keyboardType(.emailAddress)
                            .autocapitalization(.none)
                            .padding()
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                        
                        SecureField("Password", text: $password)
                            .padding()
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                        
                        if let err = errorMessage {
                            Text(err).foregroundColor(.red).font(.caption)
                        }
                        
                        Button(action: performRegister) {
                            HStack {
                                if isLoading {
                                    ProgressView()
                                        .progressViewStyle(CircularProgressViewStyle())
                                        .tint(.white)
                                } else {
                                    Text("REGISTER").fontWeight(.bold)
                                }
                            }
                            .frame(maxWidth: .infinity)
                            .padding()
                            .background(Color.red)
                            .foregroundColor(.white)
                            .cornerRadius(10)
                        }
                        .disabled(isLoading)
                    }
                    .padding(.horizontal)
                }
            }
        }
    }
    
    private func performRegister() {
        guard !name.isEmpty, !mobile.isEmpty, !email.isEmpty, !password.isEmpty else {
            errorMessage = "Please fill in all fields"
            return
        }
        isLoading = true
        errorMessage = nil
        
        NetworkService.shared.postForm(
            endpoint: "api/user_register.php",
            params: ["name": name, "mobile": mobile, "email": email, "password": password]
        ) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let resp):
                    if resp.status == "success" || resp.status == "unverified" {
                        onRequireOtp(email)
                    } else {
                        errorMessage = resp.message ?? "Registration failed"
                    }
                case .failure(let err):
                    errorMessage = err.localizedDescription
                }
            }
        }
    }
}

struct VerifyOtpView: View {
    let email: String
    @Environment(\.presentationMode) var presentationMode
    @ObservedObject var session = SessionManager.shared
    
    @State private var otp = ""
    @State private var isLoading = false
    @State private var errorMessage: String? = nil
    @State private var message: String? = nil
    
    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            
            VStack(spacing: 20) {
                Text("Verify OTP")
                    .font(.title2)
                    .fontWeight(.bold)
                    .foregroundColor(.white)
                
                Text("Enter the 6-digit OTP sent to \(email)")
                    .font(.subheadline)
                    .foregroundColor(.gray)
                    .multilineTextAlignment(.center)
                
                TextField("6-Digit OTP", text: $otp)
                    .keyboardType(.numberPad)
                    .padding()
                    .background(Color.white.opacity(0.15))
                    .cornerRadius(10)
                    .foregroundColor(.white)
                    .multilineTextAlignment(.center)
                
                if let err = errorMessage {
                    Text(err).foregroundColor(.red).font(.caption)
                }
                if let msg = message {
                    Text(msg).foregroundColor(.green).font(.caption)
                }
                
                Button(action: verifyOtp) {
                    HStack {
                        if isLoading {
                            ProgressView()
                                .progressViewStyle(CircularProgressViewStyle())
                                .tint(.white)
                        } else {
                            Text("VERIFY OTP").fontWeight(.bold)
                        }
                    }
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.red)
                    .foregroundColor(.white)
                    .cornerRadius(10)
                }
                .disabled(isLoading)
                
                Button("Resend OTP") {
                    resendOtp()
                }
                .foregroundColor(.gray)
                
                Spacer()
            }
            .padding()
        }
    }
    
    private func verifyOtp() {
        guard !otp.isEmpty else { return }
        isLoading = true
        errorMessage = nil
        
        NetworkService.shared.postForm(
            endpoint: "api/user_verify_email.php",
            params: ["email": email, "otp": otp]
        ) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let resp):
                    if resp.status == "success", let token = resp.sessionToken {
                        session.saveSession(token: token, name: resp.user?.name, email: resp.user?.email)
                        presentationMode.wrappedValue.dismiss()
                    } else {
                        errorMessage = resp.message ?? "Invalid OTP"
                    }
                case .failure(let err):
                    errorMessage = err.localizedDescription
                }
            }
        }
    }
    
    private func resendOtp() {
        NetworkService.shared.postForm(
            endpoint: "api/user_resend_otp.php",
            params: ["email": email]
        ) { result in
            DispatchQueue.main.async {
                if case .success(let resp) = result {
                    message = resp.message ?? "OTP Resent"
                }
            }
        }
    }
}

struct ForgotPasswordView: View {
    @Environment(\.presentationMode) var presentationMode
    @State private var email = ""
    @State private var otp = ""
    @State private var newPassword = ""
    @State private var step = 1
    @State private var isLoading = false
    @State private var message: String? = nil
    @State private var errorMessage: String? = nil
    
    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            
            VStack(spacing: 20) {
                Text(step == 1 ? "Forgot Password" : "Reset Password")
                    .font(.title2)
                    .fontWeight(.bold)
                    .foregroundColor(.white)
                
                if step == 1 {
                    TextField("Registered Email", text: $email)
                        .keyboardType(.emailAddress)
                        .autocapitalization(.none)
                        .padding()
                        .background(Color.white.opacity(0.15))
                        .cornerRadius(10)
                        .foregroundColor(.white)
                    
                    Button("SEND OTP") { sendForgotOtp() }
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(Color.red)
                        .foregroundColor(.white)
                        .cornerRadius(10)
                } else {
                    TextField("OTP", text: $otp)
                        .keyboardType(.numberPad)
                        .padding()
                        .background(Color.white.opacity(0.15))
                        .cornerRadius(10)
                        .foregroundColor(.white)
                    
                    SecureField("New Password", text: $newPassword)
                        .padding()
                        .background(Color.white.opacity(0.15))
                        .cornerRadius(10)
                        .foregroundColor(.white)
                    
                    Button("RESET PASSWORD") { resetPassword() }
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(Color.red)
                        .foregroundColor(.white)
                        .cornerRadius(10)
                }
                
                if let err = errorMessage { Text(err).foregroundColor(.red).font(.caption) }
                if let msg = message { Text(msg).foregroundColor(.green).font(.caption) }
                
                Spacer()
            }
            .padding()
        }
    }
    
    private func sendForgotOtp() {
        isLoading = true
        errorMessage = nil
        NetworkService.shared.postForm(
            endpoint: "api/user_forgot_password.php",
            params: ["email": email]
        ) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let resp):
                    if resp.status == "success" {
                        step = 2
                        message = "OTP sent to your email"
                    } else {
                        errorMessage = resp.message ?? "Email not found"
                    }
                case .failure(let err):
                    errorMessage = err.localizedDescription
                }
            }
        }
    }
    
    private func resetPassword() {
        isLoading = true
        errorMessage = nil
        NetworkService.shared.postForm(
            endpoint: "api/user_reset_password.php",
            params: ["email": email, "otp": otp, "new_password": newPassword]
        ) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let resp):
                    if resp.status == "success" {
                        presentationMode.wrappedValue.dismiss()
                    } else {
                        errorMessage = resp.message ?? "Failed to reset password"
                    }
                case .failure(let err):
                    errorMessage = err.localizedDescription
                }
            }
        }
    }
}
