import SwiftUI

struct MovieListView: View {
    @State private var movies: [MediaItem] = []
    @State private var isLoading = true
    @State private var errorMessage: String? = nil
    @State private var searchText = ""
    @State private var selectedMedia: MediaItem? = nil
    
    var filteredMovies: [MediaItem] {
        if searchText.isEmpty {
            return movies
        } else {
            return movies.filter { $0.title.localizedCaseInsensitiveContains(searchText) }
        }
    }
    
    var body: some View {
        NavigationView {
            ZStack {
                Color.black.ignoresSafeArea()
                
                if isLoading {
                    ProgressView("Loading Movies...")
                        .foregroundColor(.white)
                } else if let error = errorMessage {
                    VStack {
                        Text(error).foregroundColor(.red)
                        Button("Retry") { loadMovies() }
                            .padding(.top)
                    }
                } else {
                    ScrollView {
                        VStack(alignment: .leading, spacing: 15) {
                            // Search bar
                            HStack {
                                Image(systemName: "magnifyingglass").foregroundColor(.gray)
                                TextField("Search movies...", text: $searchText)
                                    .foregroundColor(.white)
                            }
                            .padding(12)
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .padding(.horizontal)
                            
                            LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 15) {
                                ForEach(filteredMovies) { movie in
                                    Button(action: {
                                        selectedMedia = movie
                                    }) {
                                        MediaCardView(item: movie)
                                    }
                                }
                            }
                            .padding(.horizontal)
                        }
                    }
                }
            }
            .navigationTitle("Movies")
            .navigationBarTitleDisplayMode(.inline)
            .fullScreenCover(item: $selectedMedia) { item in
                PlayerScreen(title: item.title, videoUrlString: item.videoUrl ?? "")
            }
            .onAppear {
                if movies.isEmpty { loadMovies() }
            }
        }
    }
    
    private func loadMovies() {
        isLoading = true
        errorMessage = nil
        NetworkService.shared.fetchMovies { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let data):
                    self.movies = data
                case .failure(let err):
                    self.errorMessage = err.localizedDescription
                }
            }
        }
    }
}

struct MediaCardView: View {
    let item: MediaItem
    
    var body: some View {
        VStack(alignment: .leading) {
            AsyncImage(url: URL(string: item.posterUrl ?? "")) { phase in
                switch phase {
                case .success(let image):
                    image.resizable().aspectRatio(2/3, contentMode: .fill)
                case .failure(_), .empty:
                    Rectangle().fill(Color.gray.opacity(0.3))
                        .aspectRatio(2/3, contentMode: .fit)
                        .overlay(Image(systemName: "film").font(.largeTitle).foregroundColor(.white))
                @unknown default:
                    EmptyView()
                }
            }
            .cornerRadius(10)
            .clipped()
            
            Text(item.title)
                .font(.subheadline)
                .fontWeight(.bold)
                .foregroundColor(.white)
                .lineLimit(1)
            
            if let rating = item.rating {
                HStack(spacing: 4) {
                    Image(systemName: "star.fill").foregroundColor(.yellow).font(.caption)
                    Text(rating).font(.caption).foregroundColor(.gray)
                }
            }
        }
    }
}
