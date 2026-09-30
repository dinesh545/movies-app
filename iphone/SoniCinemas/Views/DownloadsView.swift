import SwiftUI

struct DownloadsView: View {
    @State private var downloads: [LocalDownloadItem] = []
    
    var body: some View {
        NavigationView {
            ZStack {
                Color.black.ignoresSafeArea()
                
                if downloads.isEmpty {
                    VStack(spacing: 15) {
                        Image(systemName: "square.and.arrow.down")
                            .font(.system(size: 60))
                            .foregroundColor(.gray)
                        Text("No Downloads Yet")
                            .font(.headline)
                            .foregroundColor(.gray)
                        Text("Downloaded movies and episodes will appear here for offline playback.")
                            .font(.caption)
                            .foregroundColor(.gray)
                            .multilineTextAlignment(.center)
                            .padding(.horizontal, 40)
                    }
                } else {
                    List {
                        ForEach(downloads) { item in
                            HStack {
                                VStack(alignment: .leading) {
                                    Text(item.title)
                                        .font(.headline)
                                        .foregroundColor(.white)
                                    Text("Downloaded")
                                        .font(.caption)
                                        .foregroundColor(.green)
                                }
                                Spacer()
                                Image(systemName: "play.fill")
                                    .foregroundColor(.red)
                            }
                            .listRowBackground(Color.white.opacity(0.1))
                        }
                        .onDelete(perform: deleteDownload)
                    }
                    .listStyle(PlainListStyle())
                }
            }
            .navigationTitle("Downloads")
            .navigationBarTitleDisplayMode(.inline)
        }
    }
    
    private func deleteDownload(at offsets: IndexSet) {
        downloads.remove(atOffsets: offsets)
    }
}
