import SwiftUI

struct SuggestionView: View {
    @ObservedObject var session = SessionManager.shared
    
    @State private var category = "Movie Request"
    @State private var suggestionText = ""
    @State private var name = ""
    @State private var email = ""
    @State private var isLoading = false
    @State private var statusMessage: String? = nil
    @State private var isError = false
    
    let categories = ["Movie Request", "Web Series Request", "App Feedback", "Bug Report", "Other"]
    
    var body: some View {
        NavigationView {
            ZStack {
                Color.black.ignoresSafeArea()
                
                ScrollView {
                    VStack(alignment: .leading, spacing: 20) {
                        Text("Send Suggestion or Request")
                            .font(.headline)
                            .foregroundColor(.gray)
                        
                        Picker("Category", selection: $category) {
                            ForEach(categories, id: \.self) { cat in
                                Text(cat).tag(cat)
                            }
                        }
                        .pickerStyle(MenuPickerStyle())
                        .padding()
                        .frame(maxWidth: .infinity)
                        .background(Color.white.opacity(0.15))
                        .cornerRadius(10)
                        .foregroundColor(.white)
                        
                        TextField("Your Name", text: $name)
                            .padding()
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                        
                        TextField("Your Email", text: $email)
                            .keyboardType(.emailAddress)
                            .autocapitalization(.none)
                            .padding()
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                        
                        TextEditor(text: $suggestionText)
                            .frame(height: 120)
                            .padding(8)
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .foregroundColor(.white)
                            .overlay(
                                Group {
                                    if suggestionText.isEmpty {
                                        Text("Describe movie/series request or feedback...")
                                            .foregroundColor(.gray)
                                            .padding(.leading, 12)
                                            .padding(.top, 12)
                                    }
                                },
                                alignment: .topLeading
                            )
                        
                        if let msg = statusMessage {
                            Text(msg)
                                .foregroundColor(isError ? .red : .green)
                                .font(.caption)
                        }
                        
                        Button(action: submitSuggestion) {
                            HStack {
                                if isLoading {
                                    ProgressView()
                                        .progressViewStyle(CircularProgressViewStyle())
                                        .tint(.white)
                                } else {
                                    Text("SUBMIT SUGGESTION").fontWeight(.bold)
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
                    .padding()
                }
            }
            .navigationTitle("Suggestions")
            .navigationBarTitleDisplayMode(.inline)
            .onAppear {
                if name.isEmpty { name = session.userName ?? "" }
                if email.isEmpty { email = session.userEmail ?? "" }
            }
        }
    }
    
    private func submitSuggestion() {
        guard !suggestionText.isEmpty else {
            statusMessage = "Please enter suggestion details"
            isError = true
            return
        }
        isLoading = true
        statusMessage = nil
        
        let params: [String: String] = [
            "category": category,
            "suggestion_text": suggestionText,
            "name": name,
            "email": email,
            "session_token": session.sessionToken ?? ""
        ]
        
        NetworkService.shared.postForm(endpoint: "api/submit_suggestion.php", params: params) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let resp):
                    if resp.status == "success" {
                        statusMessage = resp.message ?? "Suggestion submitted successfully!"
                        isError = false
                        suggestionText = ""
                    } else {
                        statusMessage = resp.message ?? "Submission failed"
                        isError = true
                    }
                case .failure(let err):
                    statusMessage = err.localizedDescription
                    isError = true
                }
            }
        }
    }
}
