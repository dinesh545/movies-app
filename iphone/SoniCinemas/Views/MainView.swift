import SwiftUI

struct MainView: View {
    @ObservedObject var session = SessionManager.shared
    
    var body: some View {
        TabView {
            MovieListView()
                .tabItem {
                    Image(systemName: "film")
                    Text("Movies")
                }
            
            SeriesListView()
                .tabItem {
                    Image(systemName: "tv")
                    Text("Series")
                }
            
            SuggestionView()
                .tabItem {
                    Image(systemName: "lightbulb")
                    Text("Suggestions")
                }
            
            DownloadsView()
                .tabItem {
                    Image(systemName: "arrow.down.circle")
                    Text("Downloads")
                }
            
            ProfileView()
                .tabItem {
                    Image(systemName: "person.circle")
                    Text("Profile")
                }
        }
        .accentColor(.red)
    }
}

struct ProfileView: View {
    @ObservedObject var session = SessionManager.shared
    
    var body: some View {
        NavigationView {
            ZStack {
                Color.black.ignoresSafeArea()
                
                VStack(spacing: 20) {
                    Image(systemName: "person.crop.circle.fill")
                        .font(.system(size: 80))
                        .foregroundColor(.gray)
                    
                    Text(session.userName ?? "User")
                        .font(.title2)
                        .fontWeight(.bold)
                        .foregroundColor(.white)
                    
                    Text(session.userEmail ?? "")
                        .font(.subheadline)
                        .foregroundColor(.gray)
                    
                    Spacer()
                    
                    Button(action: {
                        session.clearSession()
                    }) {
                        HStack {
                            Image(systemName: "rectangle.portrait.and.arrow.right")
                            Text("LOGOUT").bold()
                        }
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(Color.red)
                        .foregroundColor(.white)
                        .cornerRadius(10)
                    }
                    .padding(.horizontal)
                    .padding(.bottom, 30)
                }
                .padding(.top, 40)
            }
            .navigationTitle("Profile")
            .navigationBarTitleDisplayMode(.inline)
        }
    }
}
