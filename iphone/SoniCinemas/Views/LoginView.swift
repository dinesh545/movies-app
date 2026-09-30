import SwiftUI

struct LoginView: View {
    @ObservedObject var session = SessionManager.shared
    
    @State private var loginId = ""
    @State private var password = ""
    @State private var isLoading = false
    @State private var errorMessage: String? = nil
    
    @State private var showRegister = false
    @State private var showForgotPassword = false
    @State private var showVerifyOtp = false
    @State private var pendingEmail = ""
    
    var body: some View {
        NavigationView {
            ZStack {
                Color.black.ignoresSafeArea()
                
                ScrollView {
                    VStack(spacing: 25) {
                        Spacer().frame(height: 40)
                        
                        Image(systemName: "film.fill")
                            .font(.system(size: 80))
                            .foregroundColor(.red)
                        
                        Text("SONI CINEMAS")
                            .font(.title)
                            .fontWeight(.bold)
                            .foregroundColor(.white)
                        
                        VStack(spacing: 15) {
                            TextField("Email or Mobile Number", text: $loginId)
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
                                Text(err)
                                    .foregroundColor(.red)
                                    .font(.caption)
                            }
                            
                            Button(action: performLogin) {
                                HStack {
                                    if isLoading {
                                        ProgressView()
                                            .progressViewStyle(CircularProgressViewStyle())
                                            .tint(.white)
                                    } else {
                                        Text("LOGIN").fontWeight(.bold)
                                    }
                                }
                                .frame(maxWidth: .infinity)
                                .padding()
                                .background(Color.red)
                                .foregroundColor(.white)
                                .cornerRadius(10)
                            }
                            .disabled(isLoading)
                            
                            HStack {
                                Spacer()
                                Button("Forgot Password?") {
                                    showForgotPassword = true
                                }
                                .foregroundColor(.gray)
                                .font(.subheadline)
                            }
                        }
                        .padding(.horizontal)
                        
                        Divider().background(Color.gray).padding(.horizontal)
                        
                        HStack {
                            Text("Don't have an account?").foregroundColor(.gray)
                            Button(action: {
                                showRegister = true
                            }) {
                                Text("Register Now")
                                    .bold()
                                    .foregroundColor(.red)
                            }
                        }
                    }
                }
            }
            .navigationBarHidden(true)
            .sheet(isPresented: $showRegister) {
                RegisterView(onRequireOtp: { email in
                    self.pendingEmail = email
                    self.showRegister = false
                    self.showVerifyOtp = true
                })
            }
            .sheet(isPresented: $showForgotPassword) {
                ForgotPasswordView()
            }
            .sheet(isPresented: $showVerifyOtp) {
                VerifyOtpView(email: pendingEmail)
            }
        }
    }
    
    private func performLogin() {
        guard !loginId.isEmpty, !password.isEmpty else {
            errorMessage = "Please fill in all fields"
            return
        }
        isLoading = true
        errorMessage = nil
        
        NetworkService.shared.postForm(
            endpoint: "api/user_login.php",
            params: ["login_id": loginId, "password": password]
        ) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let resp):
                    if resp.status == "success", let token = resp.sessionToken {
                        session.saveSession(token: token, name: resp.user?.name, email: resp.user?.email)
                    } else if resp.status == "unverified" {
                        self.pendingEmail = resp.user?.email ?? loginId
                        self.showVerifyOtp = true
                    } else {
                        errorMessage = resp.message ?? "Invalid login credentials"
                    }
                case .failure(let err):
                    errorMessage = err.localizedDescription
                }
            }
        }
    }
}
