import Foundation
import Combine

class SessionManager: ObservableObject {
    static let shared = SessionManager()
    
    @Published var isLoggedIn: Bool = false
    @Published var sessionToken: String? = nil
    @Published var userName: String? = nil
    @Published var userEmail: String? = nil
    
    private let tokenKey = "user_session_token"
    private let nameKey = "user_name"
    private let emailKey = "user_email"
    
    init() {
        self.sessionToken = UserDefaults.standard.string(forKey: tokenKey)
        self.userName = UserDefaults.standard.string(forKey: nameKey)
        self.userEmail = UserDefaults.standard.string(forKey: emailKey)
        self.isLoggedIn = (self.sessionToken != nil && !self.sessionToken!.isEmpty)
    }
    
    func saveSession(token: String, name: String?, email: String?) {
        UserDefaults.standard.set(token, forKey: tokenKey)
        UserDefaults.standard.set(name, forKey: nameKey)
        UserDefaults.standard.set(email, forKey: emailKey)
        
        DispatchQueue.main.async {
            self.sessionToken = token
            self.userName = name
            self.userEmail = email
            self.isLoggedIn = true
        }
    }
    
    func clearSession() {
        UserDefaults.standard.removeObject(forKey: tokenKey)
        UserDefaults.standard.removeObject(forKey: nameKey)
        UserDefaults.standard.removeObject(forKey: emailKey)
        
        DispatchQueue.main.async {
            self.sessionToken = nil
            self.userName = nil
            self.userEmail = nil
            self.isLoggedIn = false
        }
    }
}
